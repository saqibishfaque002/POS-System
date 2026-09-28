<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function items() {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }
    
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }

    public function goodReceivedNote() {
        return $this->belongsTo(GoodReceivedNote::class, 'reference_id');
    }

    public function purchaseReturns() {
        return $this->hasMany(PurchaseReturn::class, 'reference_id');
    }

    public function purchaseReturn() {
        return $this->hasOne(PurchaseReturn::class, 'reference_id');
    }
}
