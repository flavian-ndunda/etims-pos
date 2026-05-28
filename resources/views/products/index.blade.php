<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Products</h1>
            <a href="{{ route('products.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg">+ Add Product</a>
        </div>
    </x-slot>
    <div class="p-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3">Product</th>
                        <th class="text-left px-4 py-3">SKU</th>
                        <th class="text-left px-4 py-3">Price</th>
                        <th class="text-left px-4 py-3">Tax</th>
                        <th class="text-left px-4 py-3">Stock</th>
                        <th class="text-left px-4 py-3">Category</th>
                        <th class="text-left px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                        <td class="px-4 py-3">KES {{ number_format($product->price, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs
                                {{ $product->tax_type_code === 'A' ? 'bg-orange-100 text-orange-700' :
                                   ($product->tax_type_code === 'B' ? 'bg-green-100 text-green-700' :
                                   ($product->tax_type_code === 'E' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600')) }}">
                                {{ $product->taxLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 {{ $product->stock_quantity < 10 ? 'text-red-600 font-bold' : '' }}">{{ $product->stock_quantity }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $product->category->name }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:underline text-xs">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No products yet. <a href="{{ route('products.create') }}" class="text-blue-600 underline">Add one</a></td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $products->links() }}</div>
        </div>
    </div>
</x-app-layout>
