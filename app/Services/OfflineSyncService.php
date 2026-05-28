<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sale;
use Flavytech\Etims\Facades\Etims;
use Flavytech\Etims\Models\EtimsInvoice;
use Illuminate\Support\Facades\Log;

/**
 * OfflineSyncService
 *
 * Orchestrates the sync of offline sales to KRA when internet returns.
 *
 * In offline mode:
 *   - Sales are saved to SQLite immediately
 *   - InvoiceDTOs are queued as jobs in the SQLite `jobs` table
 *   - The queue worker is paused by the Electron main process
 *   - Receipts show "PENDING FISCALIZATION" status
 *
 * When internet returns:
 *   - The Electron main process resumes the queue worker
 *   - The queue worker picks up pending SubmitInvoiceJobs
 *   - Each job calls Etims::submitInvoice() against the live KRA API
 *   - InvoiceSubmitted event fires → FiscalizationListener updates Sale
 *   - Receipt can be reprinted with the KRA receipt number
 *
 * This service provides:
 *   1. A status overview of pending invoices
 *   2. Manual sync trigger (for the sync button in the UI)
 *   3. Detection of sales that are stuck (queued but job is missing)
 *   4. Recovery of orphaned sales (sale exists but no queue job)
 *
 * Architecture note: The queue does the heavy lifting. This service is
 * only needed for the status display and edge-case recovery. In the
 * normal flow, Electron resumes the worker and everything syncs automatically.
 */
class OfflineSyncService
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Get the current sync status.
     *
     * @return array{pending: int, failed: int, fiscalized_today: int, oldest_pending: ?string}
     */
    public function getStatus(): array
    {
        $pending          = Sale::pending()->count();
        $failed           = Sale::failed()->count();
        $fiscalizedToday  = Sale::today()->fiscalized()->count();
        $oldestPending    = Sale::pending()->oldest()->first();

        return [
            'pending'          => $pending,
            'failed'           => $failed,
            'fiscalized_today' => $fiscalizedToday,
            'oldest_pending'   => $oldestPending?->created_at?->diffForHumans(),
            'oldest_invoice'   => $oldestPending?->invoice_number,
        ];
    }

    /**
     * Count of invoices pending fiscalization.
     * Used by the Electron tray icon and the status bar.
     */
    public function pendingCount(): int
    {
        return Sale::pending()->count();
    }

    /**
     * Re-queue any sales that are in 'pending' status but have no
     * corresponding job in the queue (orphaned sales).
     *
     * This can happen if:
     *   - The app crashed after saving the sale but before dispatching the job
     *   - The SQLite jobs table was corrupted
     *   - A job was pruned from the failed_jobs table
     *
     * @return int Number of sales re-queued
     */
    public function recoverOrphanedSales(): int
    {
        $recovered = 0;

        // Find pending sales with no corresponding SDK record
        Sale::pending()->chunkById(50, function ($sales) use (&$recovered) {
            foreach ($sales as $sale) {
                $hasJob = EtimsInvoice::where('invoice_number', $sale->invoice_number)
                    ->whereIn('status', ['pending', 'processing'])
                    ->exists();

                if (!$hasJob) {
                    try {
                        $invoiceDto = $this->checkout->buildInvoiceDTO($sale);
                        Etims::queueInvoice($invoiceDto);
                        $recovered++;

                        Log::info('[OfflineSync] Recovered orphaned sale', [
                            'invoice_number' => $sale->invoice_number,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('[OfflineSync] Failed to recover sale', [
                            'invoice_number' => $sale->invoice_number,
                            'error'          => $e->getMessage(),
                        ]);
                    }
                }
            }
        });

        return $recovered;
    }

    /**
     * Retry all permanently failed invoices.
     *
     * Called from the "Retry All Failed" button in the dashboard.
     *
     * @return int Number of invoices re-queued
     */
    public function retryAllFailed(): int
    {
        $retried = 0;

        $failedRecords = EtimsInvoice::where('status', 'failed')->get();

        foreach ($failedRecords as $record) {
            try {
                Etims::retryFailedInvoice($record->id);

                // Reset the corresponding Sale to pending
                Sale::where('invoice_number', $record->invoice_number)
                    ->update(['status' => 'pending', 'failure_reason' => null]);

                $retried++;

            } catch (\Throwable $e) {
                Log::error('[OfflineSync] Failed to retry invoice', [
                    'invoice_number' => $record->invoice_number,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        Log::info("[OfflineSync] Retried {$retried} failed invoices");

        return $retried;
    }

    /**
     * Full sync diagnostic — returns detailed status for the dashboard.
     *
     * @return array<string, mixed>
     */
    public function diagnostic(): array
    {
        return [
            'status'             => $this->getStatus(),
            'queue_jobs'         => \DB::table('jobs')->count(),
            'failed_jobs'        => \DB::table('failed_jobs')->count(),
            'sdk_pending'        => EtimsInvoice::whereIn('status', ['pending', 'processing'])->count(),
            'sdk_submitted'      => EtimsInvoice::where('status', 'submitted')->count(),
            'sdk_failed'         => EtimsInvoice::where('status', 'failed')->count(),
            'db_size_mb'         => $this->getDatabaseSizeMb(),
        ];
    }

    private function getDatabaseSizeMb(): float
    {
        $path = database_path('database.sqlite');
        if (!file_exists($path)) return 0.0;
        return round(filesize($path) / 1024 / 1024, 2);
    }
}
