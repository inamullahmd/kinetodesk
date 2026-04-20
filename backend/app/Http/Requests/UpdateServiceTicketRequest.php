<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'diagnosis_notes' => ['nullable', 'string'],
            'status' => ['required', 'in:received,diagnosing,waiting_approval,in_progress,completed,delivered,cancelled'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'parts' => ['nullable', 'array'],
            'parts.*.product_id' => ['required_with:parts', 'integer', 'exists:products,id'],
            'parts.*.serial_id' => ['nullable', 'integer', 'exists:product_serials,id'],
            'parts.*.quantity' => ['required_with:parts', 'integer', 'min:1'],
            'parts.*.unit_price' => ['required_with:parts', 'numeric', 'min:0'],
        ];
    }
}