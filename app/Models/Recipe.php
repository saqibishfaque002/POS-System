<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipeable_id', 'recipeable_type', 'total_cost', 'instructions', 'status'
    ];

    public function recipeable()
    {
        return $this->morphTo();
    }

    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
