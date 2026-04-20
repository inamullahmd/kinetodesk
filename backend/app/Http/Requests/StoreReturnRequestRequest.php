<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

class StoreReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.condition_status' => ['required', 'in:unopened,opened,defective,damaged'],
            'items.*.resolution' => ['required', 'in:refund,exchange,credit'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Log validation errors to help diagnose failing tests
        Log::error('StoreReturnRequestRequest validation failed', $validator->errors()->toArray());
        parent::failedValidation($validator);
    }
}
