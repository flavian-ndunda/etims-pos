<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Sale;
use Flavytech\Etims\Events\InvoiceFailed;
use Flavytech\Etims\Events\InvoiceSubmitted;
use Illuminate\Support\Facades\Log;

/**
 * FiscalizationListener
 *
 * Listens to the SDK's InvoiceSubmitted and InvoiceFailed events
 * and updates the corresponding Sale record in the application DB.
 *
 * This is the correct pattern for handling async queue results:
 * the queue job fires an event, this listener updates your domain model.
 * The Sale record acts as the application's view of the fiscalization state.
 *
 * Registration in EventServiceProvider:
 *   InvoiceSubmitted::class => [FiscalizationListener::class]
 *   InvoiceFailed::class    => [FiscalizationFailedListener::class]
 */
class FiscalizationListener
{
    /**
     * Handle the InvoiceSubmitted event (fired by the SDK on success).
     *
     * Updates the Sale to 'fiscalized' and stores the KRA receipt data.
     * This fires whether the invoice was submitted sync or via queue.
     */
    public function handleSubmitted(InvoiceSubmitted $event): void
    {
        $invoiceNumber = $event->invoice->invoiceNumber;
        $response      = $event->response;

        $updated = Sale::where('invoice_number', $invoiceNumber)->update([
            'status'             => 'fiscalized',
            'kra_receipt_number' => $response->receiptNumber,
            'kra_qr_code'        => $response->qrCode,
            'kra_internal_data'  => $response->internalData,
            'failure_reason'     => null,
            'fiscalized_at'      => now(),
        ]);

        if ($updated) {
            Log::info('[POS] Sale fiscalized via SDK event', [
                'invoice_number' => $invoiceNumber,
                'receipt_number' => $response->receiptNumber,
            ]);
        } else {
            Log::warning('[POS] InvoiceSubmitted event fired but no matching Sale found', [
                'invoice_number' => $invoiceNumber,
            ]);
        }
    }

    /**
     * Handle the InvoiceFailed event (fired by the SDK on permanent failure).
     *
     * Updates the Sale to 'failed' with the failure reason.
     * This fires AFTER all queue retries are exhausted.
     */
    public function handleFailed(InvoiceFailed $event): void
    {
        $invoiceNumber = $event->invoice->invoiceNumber;

        Sale::where('invoice_number', $invoiceNumber)->update([
            'status'         => 'failed',
            'failure_reason' => $event->exception->getMessage(),
        ]);

        Log::error('[POS] Sale fiscalization permanently failed', [
            'invoice_number' => $invoiceNumber,
            'reason'         => $event->exception->getMessage(),
        ]);
    }
}
