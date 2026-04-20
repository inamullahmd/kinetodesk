<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'product_type' => ['required', 'in:inventory,service,custom_component,bundle'],
            'is_serialized' => ['required', 'boolean'],
            'unit_of_measure' => ['nullable', 'string', 'max:30'],
            'reorder_threshold' => ['nullable', 'integer', 'min:0'],
            'preferred_supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}