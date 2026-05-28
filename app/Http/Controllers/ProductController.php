<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ProductController - standard CRUD for product management.
 */
class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('category')->latest()->paginate(20);
        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::where('is_active', true)->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:200',
            'sku'              => 'required|string|max:100|unique:products',
            'price'            => 'required|numeric|min:0',
            'buying_price'     => 'nullable|numeric|min:0',
            'tax_type_code'    => 'required|in:A,B,C,D,E',
            'item_category'    => 'required|string|max:20',
            'stock_quantity'   => 'required|integer|min:0',
            'category_id'      => 'required|exists:categories,id',
            'barcode'          => 'nullable|string|max:100',
            'unit_of_measure'  => 'nullable|string|max:10',
            'description'      => 'nullable|string|max:500',
        ]);

        Product::create($data);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::where('is_active', true)->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:200',
            'price'          => 'required|numeric|min:0',
            'tax_type_code'  => 'required|in:A,B,C,D,E',
            'item_category'  => 'required|string|max:20',
            'stock_quantity' => 'required|integer|min:0',
            'category_id'    => 'required|exists:categories,id',
        ]);

        $product->update($data);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

  public function categories(): \Illuminate\View\View
{
    $categories = \App\Models\Category::withCount('products')->latest()->get();
    return view('products.categories', compact('categories'));
}

public function storeCategory(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
{
    $request->validate([
        'name'        => 'required|string|max:100|unique:categories,name',
        'description' => 'nullable|string|max:500',
    ]);

    \App\Models\Category::create([
        'name'        => $request->name,
        'description' => $request->description,
        'is_active'   => true,
    ]);

    return redirect()->route('products.categories')
        ->with('success', "Category '{$request->name}' created successfully.");

        }

public function toggleCategory(\App\Models\Category $category): \Illuminate\Http\RedirectResponse
{
    $category->update(['is_active' => !$category->is_active]);
    return back()->with('success', "Category '{$category->name}' " . ($category->is_active ? 'activated' : 'deactivated') . '.');
  }
}