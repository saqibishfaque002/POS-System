<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrderItemProperty extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function posOrderItem()
    {
        return $this->belongsTo(PosOrderItem::class);
    }

    public function propertyItem()
    {
        return $this->belongsTo(PropertyItem::class);
    }
}
