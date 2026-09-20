<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'unit_id',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'net_amount',
    ];
    public $timestamps = false;

    public function purchase_order() {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item() {
        return $this->belongsTo(IngredientItem::class, 'item_id');
    }

    public function unit() {
        return $this->belongsTo(IngredientUnit::class, 'unit_id');
    }
}
