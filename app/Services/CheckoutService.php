<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MpesaPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use Flavytech\Etims\DTOs\InvoiceDTO;
use Flavytech\Etims\DTOs\InvoiceLineDTO;
use Flavytech\Etims\Exceptions\EtimsApiException;
use Flavytech\Etims\Exceptions\EtimsIdempotencyException;
use Flavytech\Etims\Facades\Etims;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CheckoutService
 *
 * Orchestrates the POS checkout flow, including M-Pesa payment claiming.
 *
 * M-Pesa integration points:
 *   - If payment_type is MPESA and mpesa_payment_id is provided, the
 *     corresponding MpesaPayment is claimed atomically during checkout.
 *   - If the M-Pesa payment was already claimed (race condition), the
 *     checkout transaction rolls back cleanly.
 *   - A sale with payment_type MPESA but no mpesa_payment_id is invalid
 *     and will be rejected.
 *
 * The checkout flow:
 *   1. Validate the M-Pesa payment if applicable
 *   2. Persist the Sale and SaleItems (DB transaction)
 *   3. Claim the M-Pesa payment (atomic update within transaction)
 *   4. Queue the invoice for KRA fiscalization
 *   5. Clear the cart
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    /**
     * Process checkout synchronously (immediate KRA submission).
     *
     * @throws \Throwable
     */
    public function checkoutSync(array $checkoutData): Sale
    {
        return DB::transaction(function () use ($checkoutData) {
            $sale = $this->persistSale($checkoutData);

            // Claim M-Pesa payment within the transaction
           if ($checkoutData['payment_type'] === 'MPESA' && !empty($checkoutData['mpesa_payment_id'])) {
    $this->claimMpesaPayment($checkoutData, $sale);
}
            try {
                $invoiceDto = $this->buildInvoiceDTO($sale);
                $response   = Etims::submitInvoice($invoiceDto);

                $sale->update([
                    'status'             => $response->isSuccessful() ? 'fiscalized' : 'failed',
                    'kra_receipt_number' => $response->receiptNumber,
                    'kra_qr_code'        => $response->qrCode,
                    'kra_internal_data'  => $response->internalData,
                    'failure_reason'     => $response->isSuccessful() ? null : $response->resultMessage,
                    'fiscalized_at'      => $response->isSuccessful() ? now() : null,
                ]);

            } catch (EtimsIdempotencyException $e) {
                Log::info('[POS] Duplicate checkout — idempotency matched', [
                    'invoice_number' => $sale->invoice_number,
                ]);
                $sale->update(['status' => 'fiscalized']);

            } catch (EtimsApiException $e) {
                $sale->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
                Log::error('[POS] Sync fiscalization failed', [
                    'invoice_number' => $sale->invoice_number,
                    'error'          => $e->getMessage(),
                ]);
            }

            $this->cart->clear();

            return $sale->fresh(['items.product']);
        });
    }

    /**
     * Process checkout asynchronously (queue-based KRA submission).
     *
     * This is the recommended path for the desktop POS — never blocks.
     * The queue worker handles KRA submission when internet is available.
     */
    public function checkoutAsync(array $checkoutData): Sale
    {
        return DB::transaction(function () use ($checkoutData) {
            $sale = $this->persistSale($checkoutData, status: 'pending');

            // Claim M-Pesa payment within the transaction
           if ($checkoutData['payment_type'] === 'MPESA' && !empty($checkoutData['mpesa_payment_id'])) {
    $this->claimMpesaPayment($checkoutData, $sale);
}
            $invoiceDto = $this->buildInvoiceDTO($sale);
            Etims::queueInvoice($invoiceDto);

            $this->cart->clear();

            return $sale->fresh(['items.product']);
        });
    }

    /**
     * Build an InvoiceDTO from a persisted Sale.
     */
    public function buildInvoiceDTO(Sale $sale): InvoiceDTO
    {
        $sale->loadMissing('items');

        $lineItems = $sale->items->values()->map(function (SaleItem $item, int $index) {
            return InvoiceLineDTO::make([
                'item_number'    => $index + 1,
                'item_code'      => $item->product_sku,
                'item_name'      => $item->product_name,
                'quantity'       => $item->quantity,
                'unit_price'     => $item->unit_price,
                'taxable_amount' => $item->taxable_amount,
                'vat_amount'     => $item->vat_amount,
                'total_amount'   => $item->total_amount,
                'tax_type_code'  => $item->tax_type_code,
                'item_category'  => $item->item_category,
            ]);
        })->all();

        return InvoiceDTO::make([
            'invoice_number' => $sale->invoice_number,
            'supplier_pin'   => config('etims.credentials.pin'),
            'buyer_pin'      => $sale->buyer_pin ?: 'P000000000X',
            'buyer_name'     => $sale->buyer_name,
            'total_amount'   => $sale->total_amount,
            'vat_amount'     => $sale->vat_amount,
            'taxable_amount' => $sale->subtotal,
            'invoice_date'   => $sale->created_at->toDateString(),
            'invoice_type'   => 'S',
            'payment_type'   => 'MPESA',
            'currency'       => 'KES',
            'items'          => $lineItems,
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function persistSale(array $checkoutData, string $status = 'pending'): Sale
    {
        $totals        = $this->cart->totals();
        $items         = $this->cart->items();
        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $sale = Sale::create([
            'invoice_number'  => $invoiceNumber,
            'status'          => $status,
            'payment_type'    => $checkoutData['payment_type'],
            'buyer_pin'       => $checkoutData['buyer_pin'] ?? null,
            'buyer_name'      => $checkoutData['buyer_name'] ?? null,
            'subtotal'        => $totals['subtotal'],
            'vat_amount'      => $totals['vat_amount'],
            'total_amount'    => $totals['total'],
            'cashier_id'      => auth()->id(),
        ]);

        foreach ($items as $item) {
            SaleItem::create([
                'sale_id'        => $sale->id,
                'product_id'     => $item['product_id'],
                'product_name'   => $item['product_name'],
                'product_sku'    => $item['product_sku'],
                'quantity'       => $item['quantity'],
                'unit_price'     => $item['unit_price'],
                'taxable_amount' => $item['taxable_amount'],
                'vat_amount'     => $item['vat_amount'],
                'total_amount'   => $item['total_amount'],
                'tax_type_code'  => $item['tax_type_code'],
                'item_category'  => $item['item_category'],
            ]);

            \App\Models\Product::where('id', $item['product_id'])
                ->decrement('stock_quantity', $item['quantity']);
        }

        return $sale;
    }

    /**
     * Claim the M-Pesa payment for this sale atomically.
     *
     * This runs inside the DB transaction so if the claim fails,
     * the entire checkout rolls back — the sale is NOT persisted
     * and the cart remains intact for the cashier to try again.
     *
     * @throws \RuntimeException If payment not found, not confirmed, or already claimed
     */
    private function claimMpesaPayment(array $checkoutData, Sale $sale): void
    {
        $paymentId = $checkoutData['mpesa_payment_id'] ?? null;

        if (!$paymentId) {
            throw new \RuntimeException(
                'M-Pesa payment ID is required for MPESA payment type. ' .
                'Please complete M-Pesa payment verification before checking out.'
            );
        }

        $payment = MpesaPayment::findOrFail($paymentId);

        if (!$payment->isConfirmed()) {
            throw new \RuntimeException(
                "M-Pesa payment {$payment->id} has not been confirmed yet (status: {$payment->status}). " .
                'Wait for the customer to enter their PIN or verify the transaction code.'
            );
        }

        // claimForSale() is atomic — throws if already claimed
        $payment->claimForSale($sale);

        Log::info('[POS] M-Pesa payment claimed', [
            'payment_id'       => $payment->id,
            'transaction_code' => $payment->transaction_code,
            'invoice_number'   => $sale->invoice_number,
            'amount'           => $payment->amount,
        ]);
    }
}
