<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_quantity' => ['required', 'array', 'min:1'],
            'received_quantity.*' => ['required', 'integer', 'min:0'],

            'serials' => ['nullable', 'array'],
            'serials.*' => ['nullable', 'array'],
            'serials.*.*' => ['nullable', 'array'],
            'serials.*.*.serial_number' => ['required_with:serials.*.*', 'string', 'max:255'],
            'serials.*.*.warranty_expiry_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'received_quantity.required' => 'Received quantities are required.',
            'received_quantity.*.integer' => 'Each received quantity must be an integer.',
            'serials.*.*.serial_number.required_with' => 'Serial number is required for serialized products.',
        ];
    }
}