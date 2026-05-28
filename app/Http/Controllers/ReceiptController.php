<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\CheckoutService;
use Flavytech\Etims\DTOs\InvoiceResponseDTO;
use Flavytech\Etims\Facades\Etims;
use Illuminate\View\View;

/**
 * ReceiptController
 *
 * Generates KRA-compliant fiscal receipts using the SDK's ThermalReceiptBuilder.
 */
class ReceiptController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Show the receipt page for a completed sale.
     */
    public function show(Sale $sale): View
    {
        $sale->load('items.product', 'cashier');

        $receiptHtml = null;
        $qrSvg       = null;

        if ($sale->isFiscalized()) {
            $invoiceDto = $this->checkout->buildInvoiceDTO($sale);
            $response   = $this->buildResponseDto($sale);

            // Use the SDK's ThermalReceiptBuilder
            $receiptHtml = Etims::receipt($invoiceDto, $response)
                ->businessName(config('pos.business_name', 'Demo Supermarket'))
                ->businessAddress(config('pos.business_address', 'Nairobi, Kenya'))
                ->businessPhone(config('pos.business_phone', ''))
                ->branchName(config('pos.branch_name', ''))
                ->cashierName($sale->cashier?->name ?? 'Cashier')
                ->toHtml();

            try {
                $qrSvg = Etims::generateQrCode($response, 180);
            } catch (\Throwable) {
                // Non-critical
            }
        }

        return view('receipts.show', compact('sale', 'receiptHtml', 'qrSvg'));
    }

    /**
     * Render just the thermal receipt HTML for printing/PDF.
     */
    public function print(Sale $sale): \Illuminate\Http\Response
    {
        $sale->load('items', 'cashier');

        if (!$sale->isFiscalized()) {
            abort(422, 'Receipt is only available for fiscalized invoices.');
        }

        $invoiceDto = $this->checkout->buildInvoiceDTO($sale);
        $response   = $this->buildResponseDto($sale);

        $html = Etims::receipt($invoiceDto, $response)
            ->businessName(config('pos.business_name'))
            ->businessAddress(config('pos.business_address'))
            ->businessPhone(config('pos.business_phone'))
            ->cashierName($sale->cashier?->name ?? 'Cashier')
            ->toHtml();

        return response($html)->header('Content-Type', 'text/html');
    }

    private function buildResponseDto(Sale $sale): InvoiceResponseDTO
    {
        return new InvoiceResponseDTO(
            success:        true,
            resultCode:     '000',
            resultMessage:  'Processed Successfully',
            internalData:   $sale->kra_internal_data,
            qrCode:         $sale->kra_qr_code,
            receiptNumber:  $sale->kra_receipt_number,
            sdcId:          null,
            sdcDateTime:    $sale->fiscalized_at?->toIso8601String(),
        );
    }
}
