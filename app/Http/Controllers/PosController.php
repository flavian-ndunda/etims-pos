<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PosController
 *
 * Handles the POS terminal interface.
 *
 * Thin controller — all business logic lives in CartService and CheckoutService.
 * The controller only handles HTTP concerns: routing, request validation,
 * response formatting, and redirects.
 */
class PosController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Display the POS terminal screen.
     */
    public function index(Request $request): View
    {
        $categories = Category::with(['products' => fn($q) => $q->active()->inStock()])
            ->where('is_active', true)
            ->get();

        $products = Product::active()
            ->inStock()
            ->with('category')
            ->when($request->search, fn($q, $s) => $q->searchable($s))
            ->when($request->category, fn($q, $c) => $q->where('category_id', $c))
            ->orderBy('name')
            ->get();

        return view('pos.index', [
            'categories'  => $categories,
            'products'    => $products,
            'cartItems'   => $this->cart->items(),
            'cartTotals'  => $this->cart->totals(),
            'cartCount'   => $this->cart->count(),
        ]);
    }

    /**
     * Add a product to the cart (AJAX).
     */
    public function addToCart(Request $request): JsonResponse
    {
        $product  = Product::active()->inStock()->findOrFail($request->product_id);
        $quantity = (float) ($request->quantity ?? 1);

        if (!$product->hasStock($quantity)) {
            return response()->json([
                'success' => false,
                'message' => "Only {$product->stock_quantity} units of {$product->name} in stock.",
            ], 422);
        }

        $this->cart->add($product, $quantity);

        return response()->json([
            'success'    => true,
            'cart_count' => $this->cart->count(),
            'totals'     => $this->cart->totals(),
            'message'    => "{$product->name} added to cart.",
        ]);
    }

    /**
     * Update quantity of a cart item (AJAX).
     */
    public function updateCart(Request $request): JsonResponse
    {
        $this->cart->updateQuantity(
            (int) $request->product_id,
            (float) $request->quantity,
        );

        return response()->json([
            'success' => true,
            'totals'  => $this->cart->totals(),
            'items'   => $this->cart->items()->values(),
        ]);
    }

    /**
     * Remove an item from the cart (AJAX).
     */
    public function removeFromCart(Request $request): JsonResponse
    {
        $this->cart->remove((int) $request->product_id);

        return response()->json([
            'success'    => true,
            'cart_count' => $this->cart->count(),
            'totals'     => $this->cart->totals(),
        ]);
    }

    /**
     * Clear the entire cart.
     */
    public function clearCart(): JsonResponse
    {
        $this->cart->clear();

        return response()->json(['success' => true]);
    }

    /**
     * Process checkout.
     *
     * The 'mode' field in the request controls sync vs async submission:
     *   sync  → Submit to KRA immediately, wait for response
     *   async → Queue the invoice, return immediately (recommended)
     */
    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return back()->with('error', 'Cart is empty. Add products before checking out.');
        }

        $mode = $request->input('mode', 'async');

        $sale = $mode === 'sync'
            ? $this->checkout->checkoutSync($request->validated())
            : $this->checkout->checkoutAsync($request->validated());

        return redirect()
            ->route('pos.receipt', $sale->id)
            ->with('success', 'Sale completed successfully.');
    }
}
