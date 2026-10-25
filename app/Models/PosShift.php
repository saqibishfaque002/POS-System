<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_business_day_id',
        'branch_id',
        'user_id',
        'shift_number',
        'opening_float',
        'closing_cash_actual',
        'closing_cash_expected',
        'cash_variance',
        'opened_at',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_float' => 'decimal:2',
        'closing_cash_actual' => 'decimal:2',
        'closing_cash_expected' => 'decimal:2',
        'cash_variance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function businessDay(): BelongsTo
    {
        return $this->belongsTo(PosBusinessDay::class, 'pos_business_day_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'pos_shift_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosOrderPayment::class, 'pos_shift_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public static function getActiveForUser(int $branchId, int $userId): ?self
    {
        return static::where('branch_id', $branchId)
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->latest('id')
            ->first();
    }

    public static function generateShiftNumber(int $branchId): string
    {
        $today = now()->format('Ymd');
        $count = static::whereDate('created_at', now()->toDateString())
            ->where('branch_id', $branchId)
            ->count() + 1;

        return sprintf('SH-%s-B%d-%03d', $today, $branchId, $count);
    }
}
