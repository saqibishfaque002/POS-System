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
        $rules = [
            'food_group_id' => 'required|exists:food_groups,id',
            'name' => 'required|string',
            'base_price' => 'required_if:has_variations,false|numeric',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'has_variations' => 'boolean',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'menu_ids' => 'nullable|array',
            'menu_ids.*' => 'exists:menus,id',
        ];

        if ($this->boolean('has_variations')) {
            $rules['variations'] = 'required|array|min:1';
            $rules['variations.*.id'] = 'nullable|integer';
            $rules['variations.*.variation_id'] = 'required|exists:variations,id';
            $rules['variations.*.price'] = 'required|numeric';
            $rules['variations.*.sku'] = 'nullable|string';
        } else {
            $rules['variations'] = 'nullable|array';
        }

        return $rules;
    }

    protected function prepareForValidation()
    {
        $hasVariations = $this->boolean('has_variations', false);

        $mergeData = [
            'is_active' => $this->boolean('is_active', false),
            'has_variations' => $hasVariations,
        ];

        if (!$hasVariations) {
            $mergeData['variations'] = [];
        }

        $this->merge($mergeData);
    }
}
