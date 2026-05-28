{{-- M-Pesa Payments History Page --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">📱 M-Pesa Payments</h1>
            <span class="text-sm text-gray-500">All transactions — claimed and unclaimed</span>
        </div>
    </x-slot>

    <div class="p-6 space-y-5">

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Payments Today</div>
                <div class="text-3xl font-bold text-gray-800">{{ $stats['total_today'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Confirmed Today</div>
                <div class="text-3xl font-bold text-green-600">{{ $stats['confirmed_today'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Revenue Today</div>
                <div class="text-2xl font-bold text-blue-600">KES {{ number_format($stats['amount_today'], 0) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="text-sm text-gray-500">Unclaimed</div>
                <div class="text-3xl font-bold {{ $stats['unclaimed_count'] > 0 ? 'text-orange-500' : 'text-gray-300' }}">
                    {{ $stats['unclaimed_count'] }}
                </div>
                <div class="text-xs text-gray-400">Confirmed but not on invoice</div>
            </div>
        </div>

        {{-- Payments Table --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Code</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Type</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Phone</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Amount</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Status</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Invoice</th>
                        <th class="text-left px-4 py-3 text-gray-600 font-medium">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono font-semibold">
                            {{ $payment->transaction_code ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $payment->typeLabel() }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $payment->phone_number }}</td>
                        <td class="px-4 py-3 font-semibold">KES {{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $payment->statusBadgeColor() === 'green' ? 'bg-green-100 text-green-700' :
                                   ($payment->statusBadgeColor() === 'yellow' ? 'bg-yellow-100 text-yellow-700' :
                                   ($payment->statusBadgeColor() === 'red' ? 'bg-red-100 text-red-700' :
                                   'bg-gray-100 text-gray-600')) }}">
                                {{ $payment->status }}
                            </span>
                            @if(!$payment->claimed && $payment->isConfirmed())
                            <span class="ml-1 text-xs text-orange-500">unclaimed</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($payment->sale)
                            <a href="{{ route('invoices.show', $payment->sale) }}"
                               class="font-mono text-xs text-blue-600 hover:underline">
                                {{ $payment->sale->invoice_number }}
                            </a>
                            @else
                            <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $payment->paid_at?->diffForHumans() ?? $payment->created_at->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            No M-Pesa payments yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $payments->links() }}</div>
        </div>
    </div>
</x-app-layout>
