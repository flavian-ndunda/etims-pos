{{-- Operations Dashboard
    Shows real-time KRA fiscalization health, queue status, and today's sales.
--}}
<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-gray-800">Operations Dashboard</h1>
    </x-slot>

    <div class="p-6 space-y-6">

        {{-- ─── KPI Cards ────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Sales Today</div>
                <div class="text-3xl font-bold text-gray-900">{{ $stats['sales_today'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Revenue Today</div>
                <div class="text-2xl font-bold text-blue-600">KES {{ number_format($stats['revenue_today'], 0) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Fiscalized</div>
                <div class="text-3xl font-bold text-green-600">{{ $stats['fiscalized_today'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Pending Queue</div>
                <div class="text-3xl font-bold text-yellow-600">{{ $stats['pending_count'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Failed Today</div>
                <div class="text-3xl font-bold {{ $stats['failed_today'] > 0 ? 'text-red-600' : 'text-gray-300' }}">
                    {{ $stats['failed_today'] }}
                </div>
                @if($stats['failed_today'] > 0)
                <a href="{{ route('invoices.failed') }}" class="text-xs text-red-500 underline">View →</a>
                @endif
            </div>
        </div>

        {{-- ─── SDK Queue Health ─────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-semibold text-gray-700 mb-4">eTIMS SDK Queue Health</h2>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['sdk_submitted'] }}</div>
                    <div class="text-green-700 mt-1">Submitted to KRA</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['sdk_pending'] }}</div>
                    <div class="text-yellow-700 mt-1">In Queue / Processing</div>
                </div>
                <div class="text-center p-3 bg-red-50 rounded-lg">
                    <div class="text-2xl font-bold {{ $stats['sdk_failed'] > 0 ? 'text-red-600' : 'text-gray-300' }}">
                        {{ $stats['sdk_failed'] }}
                    </div>
                    <div class="text-red-700 mt-1">Failed (Dead Letter)</div>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-400">
                SDK table: etims_invoices — tracking all KRA submission attempts with retry history.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- ─── Recent Sales ─────────────────────────────────────────── --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-700">Recent Sales</h2>
                    <a href="{{ route('invoices.index') }}" class="text-sm text-blue-600 hover:underline">View all →</a>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 border-b">
                            <th class="text-left pb-2">Invoice</th>
                            <th class="text-left pb-2">Amount</th>
                            <th class="text-left pb-2">Status</th>
                            <th class="text-left pb-2">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                        <tr class="border-b border-gray-50 hover:bg-gray-50">
                            <td class="py-2">
                                <a href="{{ route('invoices.show', $sale) }}" class="font-mono text-xs text-blue-600 hover:underline">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="py-2 font-medium">KES {{ number_format($sale->total_amount, 0) }}</td>
                            <td class="py-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $sale->status === 'fiscalized' ? 'bg-green-100 text-green-700' :
                                       ($sale->status === 'failed' ? 'bg-red-100 text-red-700' :
                                       'bg-yellow-100 text-yellow-700') }}">
                                    {{ $sale->status }}
                                </span>
                            </td>
                            <td class="py-2 text-gray-400 text-xs">{{ $sale->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-400">No sales today</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ─── Failed Invoices (Dead Letter) ──────────────────────────── --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-700">Failed Invoices</h2>
                    <a href="{{ route('invoices.failed') }}" class="text-sm text-red-600 hover:underline">View all →</a>
                </div>
                @if($failedInvoices->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    <div class="text-3xl mb-2">✅</div>
                    <div class="text-sm">No failed invoices</div>
                    <div class="text-xs mt-1">All submissions are healthy</div>
                </div>
                @else
                <div class="space-y-2">
                    @foreach($failedInvoices as $failed)
                    <div class="p-3 bg-red-50 rounded-lg border border-red-100">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs text-red-700">{{ $failed->invoice_number }}</span>
                            <span class="text-xs text-red-500">{{ $failed->attempt_count }} attempts</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 truncate">{{ $failed->failure_reason }}</div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ─── SDK Integration Guide ────────────────────────────────────── --}}
        <div class="bg-gray-900 rounded-xl p-6 text-sm font-mono">
            <div class="text-green-400 mb-3">// flavytech/laravel-etims SDK — Live in this app</div>
            <div class="text-gray-300 space-y-1">
                <div><span class="text-blue-400">Etims</span>::<span class="text-yellow-300">submitInvoice</span>(<span class="text-orange-300">$invoiceDto</span>); <span class="text-gray-600">// sync</span></div>
                <div><span class="text-blue-400">Etims</span>::<span class="text-yellow-300">queueInvoice</span>(<span class="text-orange-300">$invoiceDto</span>);  <span class="text-gray-600">// async ✅ recommended</span></div>
                <div><span class="text-blue-400">Etims</span>::<span class="text-yellow-300">retryFailedInvoice</span>(<span class="text-orange-300">$id</span>); <span class="text-gray-600">// dead-letter recovery</span></div>
                <div><span class="text-blue-400">Etims</span>::<span class="text-yellow-300">generateQrCode</span>(<span class="text-orange-300">$response</span>); <span class="text-gray-600">// KRA QR code</span></div>
                <div><span class="text-blue-400">Etims</span>::<span class="text-yellow-300">receipt</span>(<span class="text-orange-300">$invoice</span>, <span class="text-orange-300">$response</span>)-><span class="text-yellow-300">toHtml</span>(); <span class="text-gray-600">// thermal receipt</span></div>
            </div>
        </div>
    </div>
</x-app-layout>
