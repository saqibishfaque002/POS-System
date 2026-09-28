<?php

namespace App\Http\Requests\Trade;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
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
            'reference_id' => 'nullable|exists:purchase_invoices,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'discount_amount' => 'numeric|min:0',
            'tax_amount' => 'numeric|min:0',
            'shipping_amount' => 'numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:ingredient_items,id',
            'items.*.unit_id' => 'required|exists:ingredient_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'numeric|min:0',
            'items.*.tax_amount' => 'numeric|min:0',
        ];
    }
}
