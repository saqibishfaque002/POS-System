<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number', 'branch_id', 'recipe_id', 'production_type', 'target_yield_qty', 
        'actual_yield_qty', 'wastage_qty', 'total_cost', 'status', 'notes', 
        'created_by', 'completed_by', 'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
