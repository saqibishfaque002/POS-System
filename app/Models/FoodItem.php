<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    use HasFactory;

    protected $fillable = ['food_group_id', 'name', 'base_price', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
    ];

    public function foodGroup()
    {
        return $this->belongsTo(FoodGroup::class);
    }

    public function variations()
    {
        return $this->hasMany(FoodItemVariation::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_food_item')
            ->withPivot(['custom_price', 'is_active'])
            ->withTimestamps();
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'food_item_menu')
            ->withPivot('price')
            ->withTimestamps();
    }
}
