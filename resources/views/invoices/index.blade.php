<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-gray-800">Invoices</h1></x-slot>
    <div class="p-6">
        <div class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Today</div>
                <div class="text-3xl font-bold">{{ $stats['sales_today'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Revenue</div>
                <div class="text-2xl font-bold text-blue-600">KES {{ number_format($stats['revenue_today'], 0) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Fiscalized</div>
                <div class="text-3xl font-bold text-green-600">{{ $stats['fiscalized_today'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Pending</div>
                <div class="text-3xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Failed</div>
                <div class="text-3xl font-bold {{ $stats['failed_today'] > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $stats['failed_today'] }}</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3">Invoice</th>
                        <th class="text-left px-4 py-3">Amount</th>
                        <th class="text-left px-4 py-3">Payment</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">KRA Receipt</th>
                        <th class="text-left px-4 py-3">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('invoices.show', $sale) }}" class="font-mono text-xs text-blue-600 hover:underline">{{ $sale->invoice_number }}</a>
                        </td>
                        <td class="px-4 py-3 font-medium">KES {{ number_format($sale->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $sale->payment_type }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $sale->status === 'fiscalized' ? 'bg-green-100 text-green-700' :
                                   ($sale->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ $sale->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $sale->kra_receipt_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $sale->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No sales yet</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $sales->links() }}</div>
        </div>
    </div>
</x-app-layout>
