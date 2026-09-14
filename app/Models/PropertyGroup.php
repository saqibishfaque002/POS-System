<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_multiple', 'is_active'];

    protected $casts = [
        'is_multiple' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function propertyItems()
    {
        return $this->hasMany(PropertyItem::class);
    }
}
