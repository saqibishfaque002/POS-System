<?php

namespace App\Http\Requests\Trade;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_id' => 'nullable|exists:purchase_requests,id',
            'date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'required|numeric|min:0',
        ];
    }
}
