<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    public function items()
    {
        return $this->hasMany(IngredientItem::class);
    }
}
