<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomBuildOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'build_template_id' => ['required', 'integer', 'exists:build_templates,id'],
            'channel' => ['required', 'in:in_store,online,phone,walk_in'],
            'labor_charge' => ['nullable', 'numeric', 'min:0'],
            'assembly_notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.component_type' => ['required', 'string', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.serial_id' => ['nullable', 'integer', 'exists:product_serials,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one component is required.',
            'items.min' => 'At least one component is required.',
        ];
    }
}