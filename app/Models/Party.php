<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'party_type',
        'contact_person',
        'phone',
        'email',
        'tax_number',
        'address',
        'city_id',
        'payment_type_id',
        'credit_limit',
        'opening_balance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function paymentTypes()
    {
        return $this->belongsToMany(PaymentType::class, 'party_payment_type');
    }

    public function posOrders()
    {
        return $this->hasMany(PosOrder::class);
    }

    public function orderTypes()
    {
        return $this->belongsToMany(OrderType::class, 'order_type_party');
    }
}
