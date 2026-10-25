<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrderItemProperty extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'extra_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function orderItem()
    {
        return $this->belongsTo(PosOrderItem::class, 'pos_order_item_id');
    }

    public function propertyItem()
    {
        return $this->belongsTo(PropertyItem::class);
    }
}
