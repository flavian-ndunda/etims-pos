{{-- POS Terminal Screen
    The main cashier-facing interface. Split layout:
    Left: Product grid with category filters and search
    Right: Cart with line items, totals, and checkout
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">POS Terminal</h1>
            <div class="flex items-center gap-3 text-sm text-gray-500">
                <span>Cashier: {{ auth()->user()->name }}</span>
                <span class="text-gray-300">|</span>
                <span>{{ now()->format('D, d M Y H:i') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="flex h-[calc(100vh-130px)] gap-4 p-4" x-data="posTerminal()">

        {{-- ═══════════════════════════════════════════════════════════════
             LEFT PANEL — Product Browser
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="flex flex-col w-3/5 bg-white rounded-xl shadow-sm overflow-hidden">

            {{-- Search and Category Filter --}}
            <div class="p-3 border-b bg-gray-50 flex gap-2">
                <input
                    type="text"
                    x-model="search"
                    @input.debounce.300ms="filterProducts()"
                    placeholder="Search by name, SKU or barcode..."
                    class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <select
                    x-model="selectedCategory"
                    @change="filterProducts()"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Product Grid --}}
            <div class="flex-1 overflow-y-auto p-3">
                <div class="grid grid-cols-3 gap-2" id="product-grid">
                    @foreach($products as $product)
                    <div
                        class="product-card border rounded-lg p-3 cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all select-none"
                        data-id="{{ $product->id }}"
                        data-name="{{ $product->name }}"
                        data-sku="{{ $product->sku }}"
                        data-price="{{ $product->price }}"
                        data-category="{{ $product->category_id }}"
                        data-stock="{{ $product->stock_quantity }}"
                        @click="addToCart({{ $product->id }})"
                    >
                        <div class="text-xs text-gray-400 font-mono mb-1">{{ $product->sku }}</div>
                        <div class="font-semibold text-gray-800 text-sm leading-tight mb-2">{{ $product->name }}</div>
                        <div class="flex items-center justify-between">
                            <span class="text-blue-600 font-bold text-sm">KES {{ number_format($product->price, 2) }}</span>
                            <span class="text-xs px-1.5 py-0.5 rounded
                                {{ $product->tax_type_code === 'A' ? 'bg-orange-100 text-orange-700' :
                                   ($product->tax_type_code === 'B' ? 'bg-green-100 text-green-700' :
                                   ($product->tax_type_code === 'E' ? 'bg-red-100 text-red-700' :
                                   'bg-gray-100 text-gray-600')) }}">
                                {{ $product->taxLabel() }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-400 mt-1">{{ $product->stock_quantity }} in stock</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             RIGHT PANEL — Cart & Checkout
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="flex flex-col w-2/5 bg-white rounded-xl shadow-sm overflow-hidden">

            {{-- Cart Header --}}
            <div class="p-3 border-b bg-gray-50 flex items-center justify-between">
                <h2 class="font-semibold text-gray-700">Current Sale</h2>
                <button
                    @click="clearCart()"
                    class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1"
                    x-show="cartItems.length > 0"
                >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear
                </button>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto p-2">
                <div x-show="cartItems.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 py-10">
                    <svg class="w-12 h-12 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-sm">Cart is empty</p>
                    <p class="text-xs mt-1">Click a product to add it</p>
                </div>

                <template x-for="item in cartItems" :key="item.product_id">
                    <div class="flex items-center gap-2 p-2 border-b border-gray-100 hover:bg-gray-50">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-800 truncate" x-text="item.product_name"></div>
                            <div class="text-xs text-gray-400" x-text="'KES ' + parseFloat(item.unit_price).toFixed(2) + ' each'"></div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                @click="updateQuantity(item.product_id, item.quantity - 1)"
                                class="w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 text-sm font-bold flex items-center justify-center"
                            >−</button>
                            <span class="w-8 text-center text-sm font-semibold" x-text="item.quantity"></span>
                            <button
                                @click="updateQuantity(item.product_id, item.quantity + 1)"
                                class="w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 text-sm font-bold flex items-center justify-center"
                            >+</button>
                        </div>
                        <div class="text-sm font-bold text-gray-800 w-20 text-right" x-text="'KES ' + parseFloat(item.total_amount).toFixed(2)"></div>
                        <button
                            @click="removeItem(item.product_id)"
                            class="text-gray-300 hover:text-red-500 ml-1"
                        >✕</button>
                    </div>
                </template>
            </div>

            {{-- Totals --}}
            <div class="border-t p-3 bg-gray-50 text-sm space-y-1">
                <div class="flex justify-between text-gray-600">
                    <span>Taxable Amount</span>
                    <span x-text="'KES ' + totals.subtotal.toFixed(2)">KES 0.00</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>VAT (16%)</span>
                    <span x-text="'KES ' + totals.vat_amount.toFixed(2)">KES 0.00</span>
                </div>
                <div class="flex justify-between font-bold text-lg text-gray-900 pt-1 border-t">
                    <span>TOTAL</span>
                    <span x-text="'KES ' + totals.total.toFixed(2)">KES 0.00</span>
                </div>
            </div>

            {{-- Checkout Form --}}
            <div class="p-3 border-t">
                <form action="{{ route('pos.checkout') }}" method="POST" id="checkout-form">
                    @csrf

                    {{-- Payment Method --}}
                    <div class="grid grid-cols-3 gap-1 mb-3">
                        @foreach(['CASH' => '💵 Cash', 'MPESA' => '📱 M-Pesa', 'CREDIT' => '💳 Credit', 'BANK' => '🏦 Bank', 'CHEQUE' => '📄 Cheque'] as $code => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_type" value="{{ $code }}" class="sr-only peer" {{ $code === 'CASH' ? 'checked' : '' }}>
                            <div class="text-center text-xs py-2 px-1 border rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-checked:font-semibold hover:bg-gray-50 transition-all">
                                {{ $label }}
                            </div>
                        </label>
                        @endforeach
                    </div>

                    {{-- Optional Buyer PIN --}}
                    <input
                        type="text"
                        name="buyer_pin"
                        placeholder="Buyer PIN (optional, for B2B)"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                    {{-- Submission Mode --}}
                    <div class="flex items-center gap-2 mb-3 text-xs text-gray-500">
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="radio" name="mode" value="async" checked class="accent-blue-600">
                            <span>Queue (async)</span>
                        </label>
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="radio" name="mode" value="sync" class="accent-green-600">
                            <span>Submit now (sync)</span>
                        </label>
                        <span class="ml-auto text-gray-400" title="Async is recommended for production">ⓘ</span>
                    </div>

                    {{-- Checkout Button --}}
                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-base transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        x-bind:disabled="cartItems.length === 0"
                    >
                        Checkout — <span x-text="'KES ' + totals.total.toFixed(2)">KES 0.00</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function posTerminal() {
        return {
            cartItems: @json($cartItems->values()),
            totals: @json($cartTotals),
            search: '',
            selectedCategory: '',

            addToCart(productId) {
                fetch('{{ route('pos.cart.add') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.refreshCart();
                    } else {
                        alert(data.message);
                    }
                });
            },

            updateQuantity(productId, quantity) {
                if (quantity <= 0) {
                    this.removeItem(productId);
                    return;
                }
                fetch('{{ route('pos.cart.update') }}', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ product_id: productId, quantity })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.cartItems = data.items;
                        this.totals = data.totals;
                    }
                });
            },

            removeItem(productId) {
                fetch('{{ route('pos.cart.remove') }}', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(r => r.json())
                .then(() => this.refreshCart());
            },

            clearCart() {
                if (!confirm('Clear the cart?')) return;
                fetch('{{ route('pos.cart.clear') }}', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(() => {
                    this.cartItems = [];
                    this.totals = { subtotal: 0, vat_amount: 0, total: 0 };
                });
            },

            refreshCart() {
                window.location.reload();
            },

            filterProducts() {
                const search = this.search.toLowerCase();
                const category = this.selectedCategory;
                document.querySelectorAll('.product-card').forEach(card => {
                    const matchSearch = !search ||
                        card.dataset.name.toLowerCase().includes(search) ||
                        card.dataset.sku.toLowerCase().includes(search);
                    const matchCategory = !category || card.dataset.category === category;
                    card.style.display = (matchSearch && matchCategory) ? 'block' : 'none';
                });
            }
        }
    }
    </script>
    @endpush
</x-app-layout>
