<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;

    public function purchase_invoice() {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function item() {
        return $this->belongsTo(FoodItem::class, 'item_id');
    }
}
