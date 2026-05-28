<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * CartService
 *
 * Manages the POS cart state using Laravel sessions.
 *
 * Architecture Decision: The cart lives in the session (not a DB table)
 * because it is transient, per-cashier, and discarded on checkout.
 * Writing every cart change to the DB would add unnecessary overhead
 * and complexity for what is fundamentally ephemeral state.
 *
 * Session key: 'pos_cart'
 * Cart structure: [product_id => ['product' => Product, 'quantity' => float]]
 *
 * The CartService is stateless — all state is in the session.
 * Inject it as a singleton or resolve it per-request.
 */
class CartService
{
    private const SESSION_KEY = 'pos_cart';

    /**
     * Add a product to the cart or increment its quantity.
     */
    public function add(Product $product, float $quantity = 1.0): void
    {
        $cart = $this->getCart();

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] += $quantity;
        } else {
            $cart[$product->id] = [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'product_sku'  => $product->sku,
                'unit_price'   => $product->price,
                'tax_type_code' => $product->tax_type_code,
                'item_category' => $product->item_category,
                'quantity'     => $quantity,
            ];
        }

        $this->saveCart($cart);
    }

    /**
     * Update the quantity of a cart item.
     * Removes the item if quantity is set to 0 or less.
     */
    public function updateQuantity(int $productId, float $quantity): void
    {
        $cart = $this->getCart();

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] = $quantity;
            }
        }

        $this->saveCart($cart);
    }

    /**
     * Remove a product from the cart entirely.
     */
    public function remove(int $productId): void
    {
        $cart = $this->getCart();
        unset($cart[$productId]);
        $this->saveCart($cart);
    }

    /**
     * Empty the cart completely.
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Get all cart items as a Collection.
     *
     * @return Collection<int, array>
     */
    public function items(): Collection
    {
        return collect($this->getCart())->map(function (array $item) {
            $qty     = $item['quantity'];
            $price   = $item['unit_price'];
            $taxCode = $item['tax_type_code'];

            $total    = round($price * $qty, 2);
            $taxable  = $taxCode === 'A' ? round($total / 1.16, 2) : $total;
            $vat      = $taxCode === 'A' ? round($total - $taxable, 2) : 0.0;

            return array_merge($item, [
                'total_amount'   => $total,
                'taxable_amount' => $taxable,
                'vat_amount'     => $vat,
            ]);
        });
    }

    /**
     * Total number of unique items in the cart.
     */
    public function count(): int
    {
        return count($this->getCart());
    }

    /**
     * Whether the cart is empty.
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Calculate cart totals.
     *
     * @return array{subtotal: float, vat_amount: float, total: float}
     */
    public function totals(): array
    {
        $items = $this->items();

        $total   = $items->sum('total_amount');
        $vat     = $items->sum('vat_amount');
        $taxable = $items->sum('taxable_amount');

        return [
            'subtotal'   => round($taxable, 2),
            'vat_amount' => round($vat, 2),
            'total'      => round($total, 2),
        ];
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /** @return array<int, array> */
    private function getCart(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /** @param array<int, array> $cart */
    private function saveCart(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }
}
