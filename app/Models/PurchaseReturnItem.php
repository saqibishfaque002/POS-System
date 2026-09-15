<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;

    public function purchase_return() {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function item() {
        return $this->belongsTo(FoodItem::class, 'item_id');
    }
}
