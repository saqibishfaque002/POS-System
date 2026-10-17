<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orderTypeId = $this->route('order_type')?->id ?? $this->route('order_type') ?? $this->route('orderType')?->id ?? $this->route('orderType');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('order_types', 'name')->ignore($orderTypeId),
            ],
            'code' => 'nullable|string|max:50',
            'service_charge_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'service_charge_percent' => $this->filled('service_charge_percent') ? (float)$this->service_charge_percent : 0,
        ]);
    }
}
