<?php

namespace App\Http\Requests\Ingredients;

use Illuminate\Foundation\Http\FormRequest;

class IngredientItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'ingredient_group_id' => 'required|exists:ingredient_groups,id',
            'name' => 'required|string',
            'base_price' => 'required|numeric',
            'description' => 'nullable|string',
            'purchase_unit_id' => 'required|exists:ingredient_units,id',
            'conversion_qty' => 'required|numeric',
            'consumption_unit_id' => 'required|exists:ingredient_units,id',
            'consumption_qty' => 'required|numeric',
            'reorder_level' => 'required|numeric',
            'is_active' => 'boolean'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', false),
        ]);
    }
}
