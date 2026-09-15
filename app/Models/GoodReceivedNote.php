<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceivedNote extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function items() {
        return $this->hasMany(GoodReceivedNoteItem::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }
    
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
