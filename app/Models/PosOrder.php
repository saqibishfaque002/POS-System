<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charges' => 'decimal:2',
        'delivery_charges' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'tendered_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'kot_printed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderType()
    {
        return $this->belongsTo(OrderType::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function businessDay()
    {
        return $this->belongsTo(PosBusinessDay::class, 'pos_business_day_id');
    }

    public function shift()
    {
        return $this->belongsTo(PosShift::class, 'pos_shift_id');
    }

    public function items()
    {
        return $this->hasMany(PosOrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PosOrderPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
