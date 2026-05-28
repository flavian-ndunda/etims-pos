{{-- Failed Invoices Dashboard
    Shows all permanently failed invoice submissions with retry controls.
    This page demonstrates the SDK's dead-letter queue recovery feature.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Failed Invoices</h1>
            <span class="text-sm text-gray-500">Dead-letter queue recovery</span>
        </div>
    </x-slot>

    <div class="p-6">

        {{-- How it works --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-sm text-amber-800">
            <strong>How retry works:</strong> When an invoice exceeds the maximum retry attempts
            ({{ config('etims.queue.max_tries', 5) }} attempts with exponential backoff:
            {{ implode('s → ', config('etims.queue.backoff', [10,30,60,120,300])) }}s),
            it is marked as permanently failed. Click <strong>Retry</strong> to re-queue it.
            The SDK's <code>Etims::retryFailedInvoice($id)</code> handles the recovery.
        </div>

        @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($failedSales->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="text-5xl mb-4">✅</div>
            <h2 class="text-xl font-semibold text-gray-700">No failed invoices</h2>
            <p class="text-gray-400 mt-2">All invoice submissions are healthy.</p>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Invoice</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Amount</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Failure Reason</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Cashier</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Date</th>
                        <th class="text-right px-4 py-3 text-gray-600 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($failedSales as $sale)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('invoices.show', $sale) }}" class="font-mono text-blue-600 hover:underline">
                                {{ $sale->invoice_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 font-medium">KES {{ number_format($sale->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-red-600 max-w-xs">
                            <div class="truncate" title="{{ $sale->failure_reason }}">
                                {{ $sale->failure_reason ?? 'Unknown error' }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $sale->cashier?->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <form action="{{ route('invoices.retry', $sale) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    onclick="return confirm('Re-queue invoice {{ $sale->invoice_number }} for retry?')"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 font-medium">
                                    🔄 Retry
                                </button>
                            </form>
                            <a href="{{ route('invoices.show', $sale) }}"
                               class="ml-1 px-3 py-1.5 bg-gray-100 text-gray-700 text-xs rounded-lg hover:bg-gray-200">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">
                {{ $failedSales->links() }}
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
