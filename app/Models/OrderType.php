<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'service_charge_percent',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'service_charge_percent' => 'decimal:2',
    ];

    public function posOrders()
    {
        return $this->hasMany(PosOrder::class);
    }

    public function recipeIngredients()
    {
        return $this->belongsToMany(RecipeIngredient::class);
    }
}
