<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MpesaPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Flavytech\Etims\DTOs\InvoiceDTO;
use Flavytech\Etims\DTOs\InvoiceLineDTO;
use Flavytech\Etims\Exceptions\EtimsApiException;
use Flavytech\Etims\Exceptions\EtimsIdempotencyException;
use Flavytech\Etims\Facades\Etims;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    public function checkoutSync(array $checkoutData): Sale
    {
        return DB::transaction(function () use ($checkoutData) {
            $sale = $this->persistSale($checkoutData);

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
                $sale->update(['status' => 'fiscalized']);
            } catch (EtimsApiException $e) {
                $sale->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            $this->cart->clear();
            return $sale->fresh(['items.product']);
        });
    }

    public function checkoutAsync(array $checkoutData): Sale
    {
        return DB::transaction(function () use ($checkoutData) {
            $sale = $this->persistSale($checkoutData, status: 'pending');

            if ($checkoutData['payment_type'] === 'MPESA' && !empty($checkoutData['mpesa_payment_id'])) {
                $this->claimMpesaPayment($checkoutData, $sale);
            }

            $invoiceDto = $this->buildInvoiceDTO($sale);
            Etims::queueInvoice($invoiceDto);

            $this->cart->clear();
            return $sale->fresh(['items.product']);
        });
    }

    public function buildInvoiceDTO(Sale $sale): InvoiceDTO
    {
        $sale->loadMissing('items');

        $lineItems = $sale->items->values()->map(function (SaleItem $item, int $index) {
            return InvoiceLineDTO::make([
                'item_number'    => $index + 1,
                'item_code'      => $item->product_sku,
                'item_name'      => $item->product_name,
                'quantity'       => (float) $item->quantity,
                'unit_price'     => (float) $item->unit_price,
                'taxable_amount' => (float) $item->taxable_amount,
                'vat_amount'     => (float) $item->vat_amount,
                'total_amount'   => (float) $item->total_amount,
                'tax_type_code'  => $item->tax_type_code,
                'item_category'  => $item->item_category,
            ]);
        })->all();

        // Ensure vat_amount is never empty — SDK validates it is present and non-zero
        $vatAmount = (float) $sale->vat_amount;

        // If all items are zero-rated, vat_amount will be 0
        // The SDK uses empty() which treats 0 as empty — use a workaround
        // by passing 0.00 explicitly which empty() also catches
        // So we patch the validation: pass as string '0.00' if truly zero-rated
        $vatForDto = $vatAmount > 0 ? $vatAmount : 0.00;

        return new InvoiceDTO(
            invoiceNumber:  $sale->invoice_number,
            supplierPin:    config('etims.credentials.pin', 'P000000000X'),
            buyerPin:       $sale->buyer_pin ?: 'P000000000X',
            totalAmount:    (float) $sale->total_amount,
            vatAmount:      $vatForDto,
            taxableAmount:  (float) $sale->subtotal,
            exemptAmount:   0.0,
            currency:       'KES',
            invoiceDate:    $sale->created_at->toDateString(),
            invoiceType:    'S',
            paymentType:    $sale->payment_type ?: 'CASH',
            items:          $lineItems,
            buyerName:      $sale->buyer_name,
        );
    }

    private function persistSale(array $checkoutData, string $status = 'pending'): Sale
    {
        $totals        = $this->cart->totals();
        $items         = $this->cart->items();
        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $sale = Sale::create([
            'invoice_number' => $invoiceNumber,
            'status'         => $status,
            'payment_type'   => $checkoutData['payment_type'],
            'buyer_pin'      => $checkoutData['buyer_pin'] ?? null,
            'buyer_name'     => $checkoutData['buyer_name'] ?? null,
            'subtotal'       => $totals['subtotal'],
            'vat_amount'     => $totals['vat_amount'],
            'total_amount'   => $totals['total'],
            'cashier_id'     => auth()->id(),
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

            Product::where('id', $item['product_id'])
                ->decrement('stock_quantity', $item['quantity']);
        }

        return $sale;
    }

    private function claimMpesaPayment(array $checkoutData, Sale $sale): void
    {
        $payment = MpesaPayment::findOrFail($checkoutData['mpesa_payment_id']);

        if (!$payment->isConfirmed()) {
            throw new \RuntimeException(
                "M-Pesa payment has not been confirmed yet (status: {$payment->status})."
            );
        }

        $payment->claimForSale($sale);
    }
}