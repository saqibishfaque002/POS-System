<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class VariationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'food_item_id' => 'required|exists:food_items,id',
            'name' => 'required|string',
            'sku' => 'nullable|string',
            'price' => 'required|numeric',
            'is_active' => 'boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', false),
        ]);
    }
}
