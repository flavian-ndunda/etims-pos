<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-gray-800">{{ isset($product) ? 'Edit Product' : 'Add Product' }}</h1>
    </x-slot>
    <div class="p-6 max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm p-6">
            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
            @endif
            <form method="POST" action="{{ isset($product) ? route('products.update', $product) : route('products.store') }}" class="space-y-4">
                @csrf
                @if(isset($product)) @method('PUT') @endif
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" {{ isset($product) ? 'readonly' : 'required' }}
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 {{ isset($product) ? 'bg-gray-50' : '' }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (KES) *</label>
                        <input type="number" name="price" step="0.01" value="{{ old('price', $product->price ?? '') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">KRA Tax Type *</label>
                        <select name="tax_type_code" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(['A'=>'A — Standard 16% VAT','B'=>'B — Zero Rated','C'=>'C — VAT Exempt','D'=>'D — Non-VATable','E'=>'E — Excisable'] as $code => $label)
                            <option value="{{ $code }}" {{ old('tax_type_code', $product->tax_type_code ?? 'A') === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">KRA Item Category Code *</label>
                        <input type="text" name="item_category" value="{{ old('item_category', $product->item_category ?? '10101501') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">e.g. 10101501 (General goods)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                        <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select name="category_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm">
                        {{ isset($product) ? 'Update Product' : 'Create Product' }}
                    </button>
                    <a href="{{ route('products.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
