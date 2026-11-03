<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosBusinessDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'business_date',
        'opened_by',
        'opened_at',
        'closed_by',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(PosShift::class, 'pos_business_day_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'pos_business_day_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function getDurationAttribute(): string
    {
        $start = $this->opened_at;
        $end = $this->closed_at ?? now();
        $diffMinutes = $start->diffInMinutes($end);
        $hours = intdiv($diffMinutes, 60);
        $mins = $diffMinutes % 60;
        $formatted = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
        return $this->closed_at ? $formatted : "Active ({$formatted})";
    }

    public static function getActiveForBranch(int $branchId): ?self
    {
        return static::where('branch_id', $branchId)
            ->where('status', 'open')
            ->latest('id')
            ->first();
    }
}
