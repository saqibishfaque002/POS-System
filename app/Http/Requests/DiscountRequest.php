<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $discountId = $this->route('discount') ? $this->route('discount')->id : null;

        return [
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('discounts', 'code')->ignore($discountId),
            ],
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    if ($this->input('type') === 'percentage' && $value > 100) {
                        $fail('Percentage discount value cannot exceed 100%.');
                    }
                },
            ],
            'scope' => 'required|in:cart,category,item',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'is_bank_discount' => 'boolean',
            'bank_name' => 'nullable|string|max:255',
            'card_type' => 'nullable|string|max:100',
            'card_numbers' => 'nullable|string',
            'party_id' => 'nullable|exists:parties,id',
            'merchant_share_percentage' => 'nullable|numeric|min:0|max:100',
            'partner_share_percentage' => 'nullable|numeric|min:0|max:100',
            'apply_tax_timing' => 'nullable|in:before_tax,after_tax',
            'apply_addon_timing' => 'nullable|in:with_addon,without_addon',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'requires_manager_approval' => 'boolean',
            'is_active' => 'boolean',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'order_type_ids' => 'nullable|array',
            'order_type_ids.*' => 'exists:order_types,id',
            'food_group_ids' => 'nullable|array',
            'food_group_ids.*' => 'exists:food_groups,id',
            'food_item_ids' => 'nullable|array',
            'food_item_ids.*' => 'exists:food_items,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'requires_manager_approval' => $this->boolean('requires_manager_approval', false),
            'min_order_amount' => $this->filled('min_order_amount') ? (float)$this->min_order_amount : null,
            'max_discount_amount' => $this->filled('max_discount_amount') ? (float)$this->max_discount_amount : null,
        ]);
    }
}
