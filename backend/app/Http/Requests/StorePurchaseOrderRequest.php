<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'expected_date' => ['required', 'date', 'after:today'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one product must be added to the purchase order.',
            'items.min' => 'At least one product must be added to the purchase order.',
            'items.*.product_id.required' => 'Each item must have a product_id.',
            'items.*.product_id.exists' => 'One or more products do not exist.',
            'items.*.quantity_ordered.required' => 'Each item must have a quantity_ordered.',
            'items.*.quantity_ordered.min' => 'Quantity must be at least 1.',
            'items.*.unit_cost.required' => 'Each item must have a unit_cost.',
            'items.*.unit_cost.min' => 'Unit cost must be greater than 0.',
        ];
    }
}
