{{-- Receipt Page
    Shown immediately after checkout.
    Shows fiscalization status, KRA receipt number, QR code, and print button.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">
                {{ $sale->isFiscalized() ? '✅ Fiscal Receipt' : ($sale->isFailed() ? '❌ Fiscalization Failed' : '⏳ Pending Fiscalization') }}
            </h1>
            <div class="flex gap-2">
                @if($sale->isFiscalized())
                <a href="{{ route('pos.receipt.print', $sale) }}" target="_blank"
                   class="px-4 py-2 bg-gray-700 text-white text-sm rounded-lg hover:bg-gray-800">
                    🖨️ Print Receipt
                </a>
                @endif
                <a href="{{ route('pos.index') }}"
                   class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    + New Sale
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- ─── Status Card ─────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">Fiscalization Status</h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Invoice Number</span>
                    <span class="font-mono font-semibold">{{ $sale->invoice_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                        {{ $sale->status === 'fiscalized' ? 'bg-green-100 text-green-700' :
                           ($sale->status === 'failed' ? 'bg-red-100 text-red-700' :
                           'bg-yellow-100 text-yellow-700') }}">
                        {{ strtoupper($sale->status) }}
                    </span>
                </div>

                @if($sale->isFiscalized())
                <div class="flex justify-between">
                    <span class="text-gray-500">KRA Receipt No.</span>
                    <span class="font-mono font-bold text-green-700">{{ $sale->kra_receipt_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Fiscalized At</span>
                    <span>{{ $sale->fiscalized_at?->format('d/m/Y H:i:s') }}</span>
                </div>
                @elseif($sale->isPending())
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-yellow-700 text-xs">
                    ⏳ Invoice queued for KRA submission. The receipt number will appear once KRA confirms.
                    This page will update automatically.
                </div>
                @elseif($sale->isFailed())
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-xs">
                    ❌ <strong>Failure reason:</strong> {{ $sale->failure_reason }}
                    <br><br>
                    <a href="{{ route('invoices.retry', $sale) }}"
                       onclick="return confirm('Retry fiscalization for this invoice?')"
                       class="underline font-semibold">Retry fiscalization →</a>
                </div>
                @endif

                <div class="pt-2 border-t">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Payment</span>
                        <span>{{ $sale->payment_type }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Amount</span>
                        <span class="font-bold text-lg">KES {{ number_format($sale->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- QR Code --}}
            @if($qrSvg)
            <div class="mt-4 flex flex-col items-center">
                <div class="border rounded-lg p-3 bg-white">
                    {!! $qrSvg !!}
                </div>
                <p class="text-xs text-gray-400 mt-2 text-center">Scan to verify at etims.kra.go.ke</p>
            </div>
            @endif
        </div>

        {{-- ─── Sale Summary ─────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">Sale Summary</h2>

            <div class="space-y-2 text-sm mb-4">
                @foreach($sale->items as $item)
                <div class="flex justify-between">
                    <div>
                        <span class="font-medium">{{ $item->product_name }}</span>
                        <span class="text-gray-400 text-xs ml-1">x{{ $item->quantity }}</span>
                    </div>
                    <span>KES {{ number_format($item->total_amount, 2) }}</span>
                </div>
                @endforeach
            </div>

            <div class="border-t pt-3 space-y-1 text-sm">
                <div class="flex justify-between text-gray-500">
                    <span>Taxable Amount</span>
                    <span>KES {{ number_format($sale->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>VAT (16%)</span>
                    <span>KES {{ number_format($sale->vat_amount, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-base border-t pt-2">
                    <span>TOTAL</span>
                    <span>KES {{ number_format($sale->total_amount, 2) }}</span>
                </div>
            </div>

            <div class="mt-4 text-xs text-gray-400 space-y-1">
                <div>Cashier: {{ $sale->cashier?->name }}</div>
                <div>Date: {{ $sale->created_at->format('d/m/Y H:i:s') }}</div>
                @if($sale->buyer_pin)
                <div>Buyer PIN: {{ $sale->buyer_pin }}</div>
                @endif
            </div>
        </div>

        {{-- ─── Thermal Receipt Preview ──────────────────────────────────── --}}
        @if($receiptHtml && $sale->isFiscalized())
        <div class="md:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">Thermal Receipt Preview</h2>
            <div class="flex justify-center">
                <div class="border-2 border-dashed border-gray-200 rounded-lg overflow-hidden" style="width:320px;">
                    {!! $receiptHtml !!}
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Auto-refresh for pending invoices --}}
    @if($sale->isPending())
    <script>
        setTimeout(() => window.location.reload(), 5000);
    </script>
    @endif
</x-app-layout>
