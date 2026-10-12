<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    public function branches()
    {
        return $this->belongsToMany(Branch::class)
                    ->withPivot('tax_percentage')
                    ->withTimestamps();
    }
}
