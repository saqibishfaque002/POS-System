<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function posOrder()
    {
        return $this->belongsTo(PosOrder::class);
    }

    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function properties()
    {
        return $this->hasMany(PosOrderItemProperty::class);
    }
}
