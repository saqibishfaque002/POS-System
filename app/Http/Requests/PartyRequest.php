<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'party_type' => 'nullable|string|in:customer,corporate,vendor,supplier,staff,delivery_partner,other',
            'order_type_ids' => 'nullable|array',
            'order_type_ids.*' => 'exists:order_types,id',
            'payment_type_ids' => 'nullable|array',
            'payment_type_ids.*' => 'exists:payment_types,id',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'payment_type_id' => 'nullable|exists:payment_types,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'party_type' => $this->filled('party_type') ? $this->party_type : 'delivery_partner',
            'payment_type_id' => $this->filled('payment_type_id') ? $this->payment_type_id : null,
            'is_active' => $this->boolean('is_active', true),
            'credit_limit' => $this->filled('credit_limit') ? (float)$this->credit_limit : 0,
            'opening_balance' => $this->filled('opening_balance') ? (float)$this->opening_balance : 0,
        ]);
    }
}
