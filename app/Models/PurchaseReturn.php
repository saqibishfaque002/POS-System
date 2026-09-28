<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function items() {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }
    
    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice() {
        return $this->belongsTo(PurchaseInvoice::class, 'reference_id');
    }
    
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
