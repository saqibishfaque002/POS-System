<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyItem extends Model
{
    use HasFactory;

    protected $fillable = ['property_group_id', 'name', 'extra_price', 'is_active'];

    protected $casts = [
        'extra_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function propertyGroup()
    {
        return $this->belongsTo(PropertyGroup::class);
    }
}
