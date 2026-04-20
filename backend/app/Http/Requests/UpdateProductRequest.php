<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id') ?? $this->route('product');

        return [
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'name' => ['sometimes', 'string', 'max:200'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'product_type' => ['sometimes', 'in:inventory,service,custom_component,bundle'],
            'is_serialized' => ['sometimes', 'boolean'],
            'unit_of_measure' => ['nullable', 'string', 'max:30'],
            'reorder_threshold' => ['nullable', 'integer', 'min:0'],
            'preferred_supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}