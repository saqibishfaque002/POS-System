<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceivedNoteItem extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;

    public function good_received_note() {
        return $this->belongsTo(GoodReceivedNote::class);
    }

    public function item() {
        return $this->belongsTo(IngredientItem::class, 'item_id');
    }

    public function unit() {
        return $this->belongsTo(IngredientUnit::class, 'unit_id');
    }
}
