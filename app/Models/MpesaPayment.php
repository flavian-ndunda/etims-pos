<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MpesaPayment
 *
 * Represents a single M-Pesa payment interaction — either:
 *   A) An STK push initiated from the POS, or
 *   B) A manually entered M-Pesa transaction code verified against Safaricom
 *
 * Lifecycle:
 *
 *   STK Push path:
 *     pending → awaiting_confirmation → completed (paid) | failed (timeout/cancelled)
 *
 *   Manual verification path:
 *     pending → verified (amount + recipient confirmed) | rejected (wrong details)
 *
 * A payment can only be applied to one invoice. The `sale_id` field is null
 * until the payment is claimed at checkout. This prevents double-spending.
 *
 * The `claimed` flag is the authoritative source of truth — a payment is
 * claimed the moment it is linked to a Sale and cannot be claimed again.
 *
 * @property int $id
 * @property string $type                    stk_push | manual_verification
 * @property string $status                  pending | awaiting_confirmation | verified | completed | failed | rejected
 * @property string $phone_number            Buyer's phone (e.g. 254712345678)
 * @property float $amount                   KES amount
 * @property string|null $transaction_code   M-Pesa reference (e.g. RGH4K2X3L1)
 * @property string|null $merchant_request_id Safaricom STK push ID
 * @property string|null $checkout_request_id Safaricom checkout ID (for polling)
 * @property string|null $result_code        Safaricom result code
 * @property string|null $result_desc        Safaricom result description
 * @property array|null $raw_callback        Full Safaricom callback payload
 * @property bool $claimed                   Whether linked to an invoice
 * @property int|null $sale_id               The invoice this payment was applied to
 * @property int $cashier_id
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon $created_at
 */
class MpesaPayment extends Model
{
    protected $fillable = [
        'type',
        'status',
        'phone_number',
        'amount',
        'transaction_code',
        'merchant_request_id',
        'checkout_request_id',
        'result_code',
        'result_desc',
        'raw_callback',
        'claimed',
        'sale_id',
        'cashier_id',
        'paid_at',
    ];

    protected $casts = [
        'amount'       => 'float',
        'claimed'      => 'boolean',
        'raw_callback' => 'array',
        'paid_at'      => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /** Payments confirmed by Safaricom but not yet linked to an invoice */
    public function scopeUnclaimed(Builder $query): Builder
    {
        return $query->where('claimed', false)
                     ->whereIn('status', ['completed', 'verified']);
    }

    /** STK push payments */
    public function scopeStkPush(Builder $query): Builder
    {
        return $query->where('type', 'stk_push');
    }

    /** Manual verification payments */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('type', 'manual_verification');
    }

    // =========================================================================
    // Status Helpers
    // =========================================================================

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'awaiting_confirmation'], true);
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, ['completed', 'verified'], true);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'rejected'], true);
    }

    public function isClaimed(): bool
    {
        return $this->claimed;
    }

    /**
     * Claim this payment for a sale.
     * Atomic — prevents race conditions via DB-level check.
     *
     * @throws \RuntimeException If already claimed
     */
    public function claimForSale(Sale $sale): void
    {
        // Use a DB update with WHERE claimed = false to ensure atomicity
        $updated = static::where('id', $this->id)
            ->where('claimed', false)
            ->update([
                'claimed' => true,
                'sale_id' => $sale->id,
            ]);

        if ($updated === 0) {
            throw new \RuntimeException(
                "M-Pesa payment {$this->transaction_code} has already been claimed for another invoice."
            );
        }

        $this->refresh();
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            'completed', 'verified' => 'green',
            'pending', 'awaiting_confirmation' => 'yellow',
            'failed', 'rejected' => 'red',
            default => 'gray',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'stk_push'            => 'STK Push',
            'manual_verification' => 'Manual Verify',
            default               => $this->type,
        };
    }
}
