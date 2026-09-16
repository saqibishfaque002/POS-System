<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_group_id', 'name', 'base_price', 'description', 'is_active',
        'purchase_unit_id', 'conversion_qty', 'consumption_unit_id', 'consumption_qty', 'reorder_level'
    ];

    public function group()
    {
        return $this->belongsTo(IngredientGroup::class, 'ingredient_group_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(IngredientUnit::class, 'purchase_unit_id');
    }

    public function consumptionUnit()
    {
        return $this->belongsTo(IngredientUnit::class, 'consumption_unit_id');
    }
}
