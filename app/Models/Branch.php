<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_charges' => 'decimal:2',
        'service_charge_value' => 'decimal:2',
    ];

    public function company() {
        return $this->belongsTo(Company::class);
    }
    public function city() {
        return $this->belongsTo(City::class);
    }
    public function floors() {
        return $this->hasMany(Floor::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'branch_menu')->withTimestamps();
    }

    public function foodItems()
    {
        return $this->belongsToMany(FoodItem::class, 'branch_food_item')
            ->withPivot(['custom_price', 'is_active'])
            ->withTimestamps();
    }

    public function paymentTypes()
    {
        return $this->belongsToMany(PaymentType::class, 'branch_payment_type')
                    ->withPivot('tax_percentage')
                    ->withTimestamps();
    }
}
