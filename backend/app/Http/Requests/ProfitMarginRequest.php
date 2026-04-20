<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfitMarginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}