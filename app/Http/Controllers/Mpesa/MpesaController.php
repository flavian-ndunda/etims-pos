<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mpesa;

use App\Http\Controllers\Controller;
use App\Models\MpesaPayment;
use App\Services\Mpesa\MpesaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MpesaController
 *
 * Handles the M-Pesa payment flows from the POS terminal.
 *
 * All endpoints are called via AJAX from the POS checkout panel.
 * They return JSON so the Alpine.js frontend can update the UI reactively.
 *
 * Two flows:
 *   STK Push:
 *     POST /mpesa/stk/initiate  → sends prompt to customer's phone
 *     GET  /mpesa/stk/status/{payment} → poll for confirmation
 *
 *   Manual Verification:
 *     POST /mpesa/verify → verify a transaction code entered by cashier
 *
 *   Payment Management:
 *     GET  /mpesa/unclaimed → list of confirmed payments not yet used
 */
class MpesaController extends Controller
{
    public function __construct(
        private readonly MpesaService $mpesa,
    ) {}

    // =========================================================================
    // STK Push
    // =========================================================================

    /**
     * POST /mpesa/stk/initiate
     *
     * Initiate an STK push to the customer's phone.
     *
     * Called from the POS checkout panel when the cashier
     * selects "M-Pesa" and clicks "Send Payment Request".
     *
     * Request body:
     *   phone  → Customer phone (07XXXXXXXX or 2547XXXXXXXX)
     *   amount → Amount in KES (from cart total)
     *   reference → Invoice number or sale reference
     */
    public function initiateStk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'     => 'required|string|min:9|max:13',
            'amount'    => 'required|numeric|min:1|max:150000',
            'reference' => 'required|string|max:20',
        ]);

        try {
            $payment = $this->mpesa->initiateStkPush(
                phone:            $data['phone'],
                amount:           $data['amount'],
                accountReference: $data['reference'],
                description:      'POS Sale Payment',
                cashierId:        auth()->id(),
            );

            return response()->json([
                'success'    => true,
                'payment_id' => $payment->id,
                'message'    => "Payment request sent to {$data['phone']}. Ask the customer to enter their M-Pesa PIN.",
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /mpesa/stk/status/{payment}
     *
     * Poll for the status of a pending STK push.
     *
     * Called by the POS frontend every 3 seconds after initiating an STK push.
     * Returns the payment status so the UI can update accordingly.
     */
    public function stkStatus(MpesaPayment $payment): JsonResponse
    {
        try {
            $payment = $this->mpesa->checkStkStatus($payment);

            return response()->json([
                'success'          => true,
                'status'           => $payment->status,
                'is_confirmed'     => $payment->isConfirmed(),
                'is_failed'        => $payment->isFailed(),
                'is_pending'       => $payment->isPending(),
                'transaction_code' => $payment->transaction_code,
                'amount'           => $payment->amount,
                'message'          => $this->statusMessage($payment),
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // =========================================================================
    // Manual Verification
    // =========================================================================

    /**
     * POST /mpesa/verify
     *
     * Verify a manually entered M-Pesa transaction code.
     *
     * Called when the customer has already paid and shows the cashier
     * their M-Pesa confirmation SMS.
     *
     * Request body:
     *   transaction_code → The M-Pesa code (e.g. RGH4K2X3L1)
     *   amount           → Expected amount (from cart total)
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'transaction_code' => 'required|string|min:6|max:20|alpha_num',
            'amount'           => 'required|numeric|min:1',
        ]);

        try {
            $payment = $this->mpesa->verifyManualTransaction(
                transactionCode: $data['transaction_code'],
                expectedAmount:  $data['amount'],
                cashierId:       auth()->id(),
            );

            return response()->json([
                'success'          => true,
                'payment_id'       => $payment->id,
                'transaction_code' => $payment->transaction_code,
                'amount'           => $payment->amount,
                'phone_number'     => $payment->phone_number,
                'message'          => "✅ Transaction {$payment->transaction_code} verified — KES " . number_format($payment->amount, 2),
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // =========================================================================
    // Unclaimed Payments
    // =========================================================================

    /**
     * GET /mpesa/unclaimed
     *
     * List all confirmed M-Pesa payments not yet applied to an invoice.
     *
     * The cashier can select one of these at checkout instead of
     * initiating a new STK push — useful when a customer paid earlier
     * and comes to the counter with their receipt.
     */
    public function unclaimed(): JsonResponse
    {
        $payments = $this->mpesa->unclaimedPayments()->map(fn($p) => [
            'id'               => $p->id,
            'type'             => $p->typeLabel(),
            'transaction_code' => $p->transaction_code,
            'phone_number'     => $this->maskPhone($p->phone_number),
            'amount'           => $p->amount,
            'amount_formatted' => 'KES ' . number_format($p->amount, 2),
            'paid_at'          => $p->paid_at?->diffForHumans(),
            'status'           => $p->status,
        ]);

        return response()->json([
            'success'  => true,
            'payments' => $payments,
        ]);
    }

    /**
     * GET /mpesa/payments
     *
     * View all M-Pesa payments (paginated) for the payments history page.
     */
    public function index(): View
    {
        $payments = MpesaPayment::with('sale', 'cashier')
            ->latest()
            ->paginate(20);

        $stats = [
            'total_today'     => MpesaPayment::whereDate('created_at', today())->count(),
            'confirmed_today' => MpesaPayment::whereDate('created_at', today())->whereIn('status', ['completed', 'verified'])->count(),
            'amount_today'    => MpesaPayment::whereDate('created_at', today())->whereIn('status', ['completed', 'verified'])->sum('amount'),
            'unclaimed_count' => MpesaPayment::unclaimed()->count(),
        ];

        return view('mpesa.index', compact('payments', 'stats'));
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function statusMessage(MpesaPayment $payment): string
    {
        return match ($payment->status) {
            'awaiting_confirmation' => 'Waiting for customer to enter M-Pesa PIN...',
            'completed'             => "✅ Payment confirmed — KES " . number_format($payment->amount, 2),
            'failed'                => '❌ Payment failed or was cancelled by customer.',
            default                 => 'Processing...',
        };
    }

    /**
     * Mask a phone number for display: 254712***678
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 9) return $phone;
        return substr($phone, 0, 6) . '***' . substr($phone, -3);
    }
}
