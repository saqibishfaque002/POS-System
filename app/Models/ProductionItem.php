<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id', 'ingredient_item_id', 'expected_qty', 
        'actual_qty', 'unit_id', 'unit_cost', 'total_cost'
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function ingredientItem()
    {
        return $this->belongsTo(IngredientItem::class);
    }

    public function unit()
    {
        return $this->belongsTo(IngredientUnit::class, 'unit_id');
    }
}
