<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'tax_percentage' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
    ];

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_menu')->withTimestamps();
    }

    public function items()
    {
        return $this->belongsToMany(FoodItem::class, 'food_item_menu')
            ->withPivot('price')
            ->withTimestamps();
    }
}
