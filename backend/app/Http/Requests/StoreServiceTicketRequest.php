<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'device_brand' => ['required', 'string', 'max:100'],
            'device_model' => ['required', 'string', 'max:100'],
            'device_serial_number' => ['nullable', 'string', 'max:255'],
            'issue_description' => ['required', 'string', 'max:1000'],
            'related_order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'related_serial_id' => ['nullable', 'integer', 'exists:product_serials,id'],
        ];
    }
}
