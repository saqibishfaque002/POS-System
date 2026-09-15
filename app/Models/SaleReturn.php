<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function items() {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }
    
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
