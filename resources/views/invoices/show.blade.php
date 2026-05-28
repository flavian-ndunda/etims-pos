<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Invoice {{ $sale->invoice_number }}</h1>
            <div class="flex gap-2">
                @if($sale->isFailed())
                <form action="{{ route('invoices.retry', $sale) }}" method="POST">
                    @csrf
                    <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg">🔄 Retry</button>
                </form>
                @endif
                @if($sale->isFiscalized())
                <a href="{{ route('pos.receipt', $sale) }}" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg">🖨️ Receipt</a>
                @endif
                <a href="{{ route('invoices.index') }}" class="px-4 py-2 border border-gray-300 text-sm rounded-lg">← Back</a>
            </div>
        </div>
    </x-slot>
    <div class="p-6 max-w-3xl">
        <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Status:</span>
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $sale->isFiscalized() ? 'bg-green-100 text-green-700' : ($sale->isFailed() ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ strtoupper($sale->status) }}
                    </span>
                </div>
                <div><span class="text-gray-500">Payment:</span> <span class="ml-2">{{ $sale->payment_type }}</span></div>
                <div><span class="text-gray-500">KRA Receipt:</span> <span class="ml-2 font-mono">{{ $sale->kra_receipt_number ?? '—' }}</span></div>
                <div><span class="text-gray-500">Total:</span> <span class="ml-2 font-bold">KES {{ number_format($sale->total_amount, 2) }}</span></div>
                <div><span class="text-gray-500">VAT:</span> <span class="ml-2">KES {{ number_format($sale->vat_amount, 2) }}</span></div>
                <div><span class="text-gray-500">Date:</span> <span class="ml-2">{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
            </div>
            @if($sale->failure_reason)
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                <strong>Failure:</strong> {{ $sale->failure_reason }}
            </div>
            @endif
            <div class="border-t pt-4">
                <h3 class="font-semibold mb-3">Line Items</h3>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="text-left py-2 px-3">Product</th>
                        <th class="text-left py-2 px-3">Qty</th>
                        <th class="text-left py-2 px-3">Price</th>
                        <th class="text-left py-2 px-3">Total</th>
                        <th class="text-left py-2 px-3">VAT</th>
                    </tr></thead>
                    <tbody>
                        @foreach($sale->items as $item)
                        <tr class="border-b">
                            <td class="py-2 px-3">{{ $item->product_name }}</td>
                            <td class="py-2 px-3">{{ $item->quantity }}</td>
                            <td class="py-2 px-3">KES {{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-2 px-3">KES {{ number_format($item->total_amount, 2) }}</td>
                            <td class="py-2 px-3">KES {{ number_format($item->vat_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
