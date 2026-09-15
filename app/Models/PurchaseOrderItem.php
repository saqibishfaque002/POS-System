<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;

    public function purchase_order() {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item() {
        return $this->belongsTo(FoodItem::class, 'item_id');
    }
}
