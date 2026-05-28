<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\CheckoutService;
use Flavytech\Etims\Facades\Etims;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * InvoiceController
 *
 * Handles invoice management: listing, viewing, retrying failed invoices.
 */
class InvoiceController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    public function index(): View
    {
        $sales = Sale::with('cashier')
            ->latest()
            ->paginate(20);

      $stats = [
    'sales_today'      => Sale::today()->count(),
    'revenue_today'    => Sale::today()->fiscalized()->sum('total_amount'),
    'fiscalized_today' => Sale::today()->fiscalized()->count(),
    'failed_today'     => Sale::today()->failed()->count(),
    'pending'          => Sale::pending()->count(),
];

        return view('invoices.index', compact('sales', 'stats'));
    }

    public function show(Sale $sale): View
    {
        $sale->load('items.product', 'cashier');

        $qrSvg = null;
        if ($sale->isFiscalized() && $sale->kra_qr_code) {
            try {
                $response = new \Flavytech\Etims\DTOs\InvoiceResponseDTO(
                    success: true,
                    resultCode: '000',
                    resultMessage: 'OK',
                    internalData: $sale->kra_internal_data,
                    qrCode: $sale->kra_qr_code,
                    receiptNumber: $sale->kra_receipt_number,
                    sdcId: null,
                    sdcDateTime: null,
                );
                $qrSvg = Etims::generateQrCode($response, 150);
            } catch (\Throwable) {
                // QR not critical — receipt still shows without it
            }
        }

        return view('invoices.show', compact('sale', 'qrSvg'));
    }

    public function failed(): View
    {
        $failedSales = Sale::failed()
            ->with('cashier', 'items')
            ->latest()
            ->paginate(15);

        return view('invoices.failed', compact('failedSales'));
    }

    /**
     * Retry a failed invoice by re-queuing it.
     *
     * This demonstrates the SDK's dead-letter recovery:
     * Etims::retryFailedInvoice() re-queues from the etims_invoices table.
     */
    public function retry(Sale $sale): RedirectResponse
    {
        if (!$sale->isFailed()) {
            return back()->with('error', 'Only failed invoices can be retried.');
        }

        try {
            // Find the SDK's etims_invoices record for this sale
            $etimsRecord = \Flavytech\Etims\Models\EtimsInvoice::where(
                'invoice_number', $sale->invoice_number
            )->latest()->first();

            if ($etimsRecord) {
                // Use the SDK's built-in retry
                Etims::retryFailedInvoice($etimsRecord->id);
            } else {
                // No SDK record — build and queue fresh
                $invoiceDto = $this->checkout->buildInvoiceDTO($sale);
                Etims::queueInvoice($invoiceDto);
            }

            $sale->update(['status' => 'pending', 'failure_reason' => null]);

            return back()->with('success', "Invoice {$sale->invoice_number} has been queued for retry.");

        } catch (\Throwable $e) {
            return back()->with('error', "Retry failed: {$e->getMessage()}");
        }
    }
}
