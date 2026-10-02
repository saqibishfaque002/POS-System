<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodItemVariation extends Model
{
    use HasFactory;

    protected $fillable = ['food_item_id', 'variation_id', 'sku', 'price', 'is_active'];

    protected $with = ['foodItem', 'variation'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
}
