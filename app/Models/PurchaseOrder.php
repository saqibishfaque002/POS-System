<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'document_number',
        'date',
        'delivery_date',
        'status',
        'reference_id',
        'total_amount',
        'notes',
        'created_by',
    ];

    public function items() {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
