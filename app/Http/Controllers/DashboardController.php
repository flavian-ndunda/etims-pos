<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Sale;
use Flavytech\Etims\Models\EtimsInvoice;
use Flavytech\Etims\Facades\Etims;
use Illuminate\View\View;

/**
 * DashboardController
 *
 * Operational dashboard showing KRA fiscalization health and queue status.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            // Today's sales
            'sales_today'      => Sale::today()->count(),
            'revenue_today'    => Sale::today()->fiscalized()->sum('total_amount'),
            'fiscalized_today' => Sale::today()->fiscalized()->count(),
            'failed_today'     => Sale::today()->failed()->count(),
            'pending_count'    => Sale::pending()->count(),

            // Queue health (SDK tables)
            'sdk_submitted'    => EtimsInvoice::where('status', 'submitted')->count(),
            'sdk_failed'       => EtimsInvoice::where('status', 'failed')->count(),
            'sdk_pending'      => EtimsInvoice::whereIn('status', ['pending', 'processing'])->count(),
        ];

        $recentSales = Sale::with('cashier')
            ->latest()
            ->limit(10)
            ->get();

        $failedInvoices = Etims::failedInvoices()->take(5);

        return view('dashboard.index', compact('stats', 'recentSales', 'failedInvoices'));
    }
}
