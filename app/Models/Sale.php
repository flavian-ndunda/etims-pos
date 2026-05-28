<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sale
 *
 * Represents a completed POS sale transaction.
 *
 * A Sale is the application-domain record of what was sold.
 * The etims_invoices record (from the SDK) is the KRA-domain record.
 * They are linked via invoice_number.
 *
 * States:
 *   draft       → Cart is being built (not yet checked out)
 *   pending     → Checkout complete, waiting for fiscalization
 *   fiscalized  → KRA accepted the invoice
 *   failed      → KRA rejected or all retries exhausted
 *   refunded    → Credit note submitted for this sale
 *
 * @property int $id
 * @property string $invoice_number      Format: INV-{YYYYMMDD}-{id}
 * @property string $status
 * @property string $payment_type
 * @property string|null $buyer_pin
 * @property string|null $buyer_name
 * @property float $subtotal
 * @property float $vat_amount
 * @property float $total_amount
 * @property string|null $kra_receipt_number
 * @property string|null $kra_qr_code
 * @property string|null $failure_reason
 * @property int $cashier_id
 * @property \Carbon\Carbon $created_at
 */
class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'status',
        'payment_type',
        'buyer_pin',
        'buyer_name',
        'subtotal',
        'vat_amount',
        'total_amount',
        'kra_receipt_number',
        'kra_qr_code',
        'kra_internal_data',
        'failure_reason',
        'cashier_id',
        'fiscalized_at',
    ];

    protected $casts = [
        'subtotal'      => 'float',
        'vat_amount'    => 'float',
        'total_amount'  => 'float',
        'fiscalized_at' => 'datetime',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeFiscalized(Builder $query): Builder
    {
        return $query->where('status', 'fiscalized');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    // Helpers
    public function isFiscalized(): bool
    {
        return $this->status === 'fiscalized';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            'fiscalized' => 'green',
            'failed'     => 'red',
            'pending'    => 'yellow',
            'processing' => 'blue',
            'refunded'   => 'purple',
            default      => 'gray',
        };
    }
}
