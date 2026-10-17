<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'customer_type' => 'nullable|string|in:regular,vip,corporate,walk_in',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'customer_type' => $this->customer_type ?: 'regular',
            'credit_limit' => $this->filled('credit_limit') ? (float)$this->credit_limit : 0,
            'opening_balance' => $this->filled('opening_balance') ? (float)$this->opening_balance : 0,
        ]);
    }
}
