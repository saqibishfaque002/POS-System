<?php

namespace App\Http\Requests\Ingredients;

use Illuminate\Foundation\Http\FormRequest;

class IngredientGroupRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string',
            'description' => 'nullable|string',
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
