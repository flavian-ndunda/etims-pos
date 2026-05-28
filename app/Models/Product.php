<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product
 *
 * Represents a sellable product in the POS system.
 *
 * Each product carries:
 *   - KRA tax type code (A/B/C/D/E) — determines VAT treatment
 *   - KRA item category code — used in eTIMS item registration
 *   - SKU — used as the item_code in InvoiceLineDTO
 *
 * Architecture note: Products are the bridge between the POS domain and
 * the KRA eTIMS domain. When a product is sold, its tax_type_code and
 * sku flow directly into the InvoiceLineDTO that gets submitted to KRA.
 *
 * @property int $id
 * @property string $name
 * @property string $sku
 * @property float $price              Unit price (tax inclusive)
 * @property float $buying_price       Cost price (for margin reporting)
 * @property string $tax_type_code     KRA code: A=16%VAT, B=Zero, C=Exempt, E=Excisable
 * @property string $item_category     KRA item classification code
 * @property int $stock_quantity
 * @property int $category_id
 * @property bool $is_active
 * @property string|null $barcode
 * @property string|null $description
 * @property string|null $unit_of_measure
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'price',
        'buying_price',
        'tax_type_code',
        'item_category',
        'stock_quantity',
        'category_id',
        'is_active',
        'barcode',
        'description',
        'unit_of_measure',
    ];

    protected $casts = [
        'price'          => 'float',
        'buying_price'   => 'float',
        'stock_quantity' => 'integer',
        'is_active'      => 'boolean',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeSearchable(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%");
        });
    }

    // =========================================================================
    // Tax Calculation Helpers
    // =========================================================================

    /**
     * Calculate the VAT amount for a given quantity.
     *
     * VAT is calculated based on the KRA tax type code.
     * Standard rate (A) = 16% of taxable amount.
     * All other codes = 0 VAT.
     *
     * Note: The SDK price is VAT-inclusive (as required by KRA retail rules).
     * We back-calculate: taxable = total / 1.16, vat = total - taxable
     */
    public function vatAmount(float $quantity = 1): float
    {
        if ($this->tax_type_code !== 'A') {
            return 0.0;
        }

        $total    = $this->price * $quantity;
        $taxable  = round($total / 1.16, 4);

        return round($total - $taxable, 2);
    }

    public function taxableAmount(float $quantity = 1): float
    {
        $total = $this->price * $quantity;

        if ($this->tax_type_code !== 'A') {
            return $total;
        }

        return round($total / 1.16, 2);
    }

    public function totalAmount(float $quantity = 1): float
    {
        return round($this->price * $quantity, 2);
    }

    public function taxLabel(): string
    {
        return match ($this->tax_type_code) {
            'A' => 'VAT 16%',
            'B' => 'Zero Rated',
            'C' => 'Exempt',
            'D' => 'Non-VATable',
            'E' => 'Excisable',
            default => 'N/A',
        };
    }

    public function isVatable(): bool
    {
        return $this->tax_type_code === 'A';
    }

    public function hasStock(float $requested): bool
    {
        return $this->stock_quantity >= $requested;
    }
}
