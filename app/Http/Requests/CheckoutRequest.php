<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_type' => 'required|in:CASH,MPESA,CREDIT,BANK,CHEQUE',
            'buyer_pin'    => 'nullable|string|max:20',
            'buyer_name'   => 'nullable|string|max:200',
            'mode'         => 'nullable|in:sync,async',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_type.required' => 'Please select a payment method.',
            'payment_type.in'       => 'Invalid payment method selected.',
        ];
    }
}
