<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SaleItem
 *
 * A single line item within a Sale.
 * Maps directly to an InvoiceLineDTO when building the KRA payload.
 *
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property string $product_name     Snapshot at time of sale
 * @property string $product_sku      Snapshot at time of sale
 * @property float $quantity
 * @property float $unit_price
 * @property float $taxable_amount
 * @property float $vat_amount
 * @property float $total_amount
 * @property string $tax_type_code
 * @property string $item_category
 */
class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'taxable_amount',
        'vat_amount',
        'total_amount',
        'tax_type_code',
        'item_category',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'unit_price'     => 'float',
        'taxable_amount' => 'float',
        'vat_amount'     => 'float',
        'total_amount'   => 'float',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
