<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mpesa;

use App\Http\Controllers\Controller;
use App\Models\MpesaPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * MpesaCallbackController
 *
 * Receives and processes inbound callbacks from Safaricom.
 *
 * Safaricom calls this URL after an STK push is confirmed or cancelled.
 * This endpoint must be publicly accessible — Safaricom's servers POST to it.
 *
 * Security:
 *   - Excluded from CSRF middleware (server-to-server call)
 *   - Validates the callback structure before processing
 *   - Always returns 200 — Safaricom will retry on non-200 responses
 *   - No sensitive data is logged
 *
 * For the desktop POS, this URL must be exposed via ngrok or a similar
 * tunnel in development. In production, it should be a public URL.
 *
 * Alternatively, the POS can use polling-only mode (no callback needed):
 *   Set MPESA_USE_CALLBACK=false in .env to rely on polling only.
 *   The MpesaService.checkStkStatus() handles this case automatically.
 *
 * STK Callback payload structure from Safaricom:
 * {
 *   "Body": {
 *     "stkCallback": {
 *       "MerchantRequestID": "xxx",
 *       "CheckoutRequestID": "xxx",
 *       "ResultCode": 0,           // 0 = success, 1032 = cancelled
 *       "ResultDesc": "...",
 *       "CallbackMetadata": {
 *         "Item": [
 *           {"Name": "Amount", "Value": 1500},
 *           {"Name": "MpesaReceiptNumber", "Value": "RGH4K2X3L1"},
 *           {"Name": "TransactionDate", "Value": 20240115103045},
 *           {"Name": "PhoneNumber", "Value": 254712345678}
 *         ]
 *       }
 *     }
 *   }
 * }
 */
class MpesaCallbackController extends Controller
{
    /**
     * Handle Safaricom STK push callback.
     *
     * POST /api/mpesa/callback
     */
    public function stkCallback(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('[Mpesa Callback] STK push callback received', [
            'checkout_request_id' => data_get($payload, 'Body.stkCallback.CheckoutRequestID'),
            'result_code'         => data_get($payload, 'Body.stkCallback.ResultCode'),
        ]);

        try {
            $callback = $payload['Body']['stkCallback'] ?? null;

            if (!$callback) {
                Log::warning('[Mpesa Callback] Invalid payload structure');
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
            $resultCode        = (string) ($callback['ResultCode'] ?? '');
            $resultDesc        = $callback['ResultDesc'] ?? '';
            $metadata          = $callback['CallbackMetadata']['Item'] ?? [];

            // Find the matching MpesaPayment record
            $payment = MpesaPayment::where('checkout_request_id', $checkoutRequestId)->first();

            if (!$payment) {
                Log::warning('[Mpesa Callback] No payment found for checkout request', [
                    'checkout_request_id' => $checkoutRequestId,
                ]);
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            if ($resultCode === '0') {
                // Payment successful
                $receiptNumber = $this->extractMetadata($metadata, 'MpesaReceiptNumber');
                $amount        = $this->extractMetadata($metadata, 'Amount');
                $phone         = $this->extractMetadata($metadata, 'PhoneNumber');

                $payment->update([
                    'status'           => 'completed',
                    'result_code'      => $resultCode,
                    'result_desc'      => $resultDesc,
                    'transaction_code' => $receiptNumber,
                    'amount'           => (float) ($amount ?? $payment->amount),
                    'phone_number'     => (string) ($phone ?? $payment->phone_number),
                    'paid_at'          => now(),
                    'raw_callback'     => $payload,
                ]);

                Log::info('[Mpesa Callback] Payment completed', [
                    'transaction_code' => $receiptNumber,
                    'amount'           => $amount,
                    'phone'            => $phone,
                ]);

            } else {
                // Payment failed or cancelled
                $payment->update([
                    'status'       => 'failed',
                    'result_code'  => $resultCode,
                    'result_desc'  => $resultDesc,
                    'raw_callback' => $payload,
                ]);

                Log::info('[Mpesa Callback] Payment failed', [
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);
            }

        } catch (\Throwable $e) {
            // Always return 200 to Safaricom — never let them retry
            Log::error('[Mpesa Callback] Processing error', ['error' => $e->getMessage()]);
        }

        // Safaricom requires this exact response format
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Confirmation Service request accepted successfully',
        ]);
    }

    /**
     * Handle Safaricom Transaction Status result callback.
     *
     * POST /api/mpesa/result
     * Used for manual verification transaction status queries.
     */
    public function transactionResult(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('[Mpesa Callback] Transaction status result', [
            'transaction_id' => data_get($payload, 'Result.TransactionID'),
            'result_code'    => data_get($payload, 'Result.ResultCode'),
        ]);

        // Store the result for pickup by the verification polling mechanism
        $transactionId = data_get($payload, 'Result.TransactionID');
        $resultCode    = (string) data_get($payload, 'Result.ResultCode', '');

        if ($transactionId && $resultCode === '0') {
            $params = collect(data_get($payload, 'Result.ResultParameters.ResultParameter', []));

            MpesaPayment::where('transaction_code', $transactionId)
                ->where('status', 'pending')
                ->update([
                    'status'       => 'verified',
                    'result_code'  => $resultCode,
                    'raw_callback' => $payload,
                    'paid_at'      => now(),
                ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Extract a value from Safaricom's CallbackMetadata Item array.
     *
     * @param array<int, array{Name: string, Value: mixed}> $items
     */
    private function extractMetadata(array $items, string $name): mixed
    {
        foreach ($items as $item) {
            if (($item['Name'] ?? '') === $name) {
                return $item['Value'] ?? null;
            }
        }
        return null;
    }
}
