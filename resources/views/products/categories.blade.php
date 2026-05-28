<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Categories</h1>
            <a href="{{ route('products.index') }}" class="px-4 py-2 border border-gray-300 text-sm rounded-lg">← Products</a>
        </div>
    </x-slot>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Add Category Form --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">Add New Category</h2>
            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
            @endif
            @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                ✅ {{ session('success') }}
            </div>
            @endif
            <form method="POST" action="{{ route('products.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="e.g. Household Goods"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}"
                           placeholder="Optional description"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm">
                    + Add Category
                </button>
            </form>
        </div>

        {{-- Existing Categories --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-4">Existing Categories</h2>
            <div class="space-y-2">
                @forelse($categories as $category)
                <div class="flex items-center justify-between p-3 border rounded-lg
                    {{ $category->is_active ? 'border-gray-200' : 'border-gray-100 bg-gray-50 opacity-60' }}">
                    <div>
                        <div class="font-medium text-sm">{{ $category->name }}</div>
                        <div class="text-xs text-gray-400">{{ $category->products_count }} products</div>
                        @if($category->description)
                        <div class="text-xs text-gray-400 mt-0.5">{{ $category->description }}</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <form method="POST" action="{{ route('products.categories.toggle', $category) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50">
                                {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <p class="text-gray-400 text-sm">No categories yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>