<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * SyncController
 *
 * Handles the offline sync status API and manual sync operations.
 *
 * The Electron main process polls /api/pending-count every time
 * a job is processed to keep the tray icon count accurate.
 *
 * The sync dashboard page is accessible from the POS UI to show
 * cashiers how many invoices are pending and let them trigger a manual sync.
 */
class SyncController extends Controller
{
    public function __construct(
        private readonly OfflineSyncService $sync,
    ) {}

    /**
     * GET /api/pending-count
     *
     * Called by the Electron main process to update the tray icon.
     * Must be fast — no auth required (localhost only, trusted).
     */
    public function pendingCount(): JsonResponse
    {
        return response()->json([
            'count' => $this->sync->pendingCount(),
        ]);
    }

    /**
     * GET /api/sync/status
     *
     * Full sync diagnostic for the status page.
     */
    public function status(): JsonResponse
    {
        return response()->json($this->sync->diagnostic());
    }

    /**
     * GET /sync
     *
     * The sync status dashboard page.
     */
    public function dashboard(): View
    {
        $diagnostic = $this->sync->diagnostic();
        return view('sync.dashboard', compact('diagnostic'));
    }

    /**
     * POST /sync/retry-all
     *
     * Re-queue all permanently failed invoices.
     */
    public function retryAll(): RedirectResponse
    {
        $retried = $this->sync->retryAllFailed();

        return back()->with('success', "Re-queued {$retried} failed invoice(s) for retry.");
    }

    /**
     * POST /sync/recover
     *
     * Recover orphaned sales (pending but no queue job).
     */
    public function recover(): RedirectResponse
    {
        $recovered = $this->sync->recoverOrphanedSales();

        return back()->with('success', "Recovered {$recovered} orphaned sale(s).");
    }
}
