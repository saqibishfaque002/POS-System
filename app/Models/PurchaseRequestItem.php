<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;

    public function purchase_request() {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item() {
        return $this->belongsTo(IngredientItem::class, 'item_id');
    }
}
