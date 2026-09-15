<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleInvoiceItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;

    public function sale_invoice() {
        return $this->belongsTo(SaleInvoice::class);
    }

    public function item() {
        return $this->belongsTo(FoodItem::class, 'item_id');
    }
}
