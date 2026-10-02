<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class FoodItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'food_group_id' => 'required|exists:food_groups,id',
            'name' => 'required|string',
            'base_price' => 'required_if:has_variations,false|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'has_variations' => 'boolean',
            'variations' => 'required_if:has_variations,true|array',
            'variations.*.id' => 'nullable|integer', // For updating existing ones
            'variations.*.variation_id' => 'required|exists:variations,id',
            'variations.*.price' => 'required|numeric',
            'variations.*.sku' => 'nullable|string',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', false),
        ]);
    }
}
