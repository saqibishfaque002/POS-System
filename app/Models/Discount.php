<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Discount extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'days_of_week' => 'array',
        'requires_manager_approval' => 'boolean',
        'is_active' => 'boolean',
        'is_bank_discount' => 'boolean',
        'merchant_share_percentage' => 'decimal:2',
        'partner_share_percentage' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'discount_branch');
    }

    public function orderTypes()
    {
        return $this->belongsToMany(OrderType::class, 'discount_order_type');
    }

    public function foodGroups()
    {
        return $this->belongsToMany(FoodGroup::class, 'discount_food_group');
    }

    public function foodItems()
    {
        return $this->belongsToMany(FoodItem::class, 'discount_food_item');
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeStandardDiscounts($query)
    {
        return $query->where('is_bank_discount', false);
    }

    public function scopeBankDiscounts($query)
    {
        return $query->where('is_bank_discount', true);
    }

    /**
     * Check if the discount is applicable to a given order context
     */
    public function isApplicableTo(?int $branchId, ?int $orderTypeId, float $subtotal, ?Carbon $now = null, ?string $cardNumber = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = $now ?? Carbon::now();

        // 1. Minimum order amount check
        if (!is_null($this->min_order_amount) && $subtotal < (float)$this->min_order_amount) {
            return false;
        }

        // 2. Single DateTime range check (valid_from and valid_to)
        if (!empty($this->valid_from) && $now->lt($this->valid_from)) {
            return false;
        }
        if (!empty($this->valid_to) && $now->gt($this->valid_to)) {
            return false;
        }

        // 3. Fallback date range check (if legacy separate fields are populated)
        $todayDate = $now->format('Y-m-d');
        if (!empty($this->start_date) && $todayDate < $this->start_date->format('Y-m-d')) {
            return false;
        }
        if (!empty($this->end_date) && $todayDate > $this->end_date->format('Y-m-d')) {
            return false;
        }

        // 4. Time window check (e.g. Happy Hour 16:00 to 19:00)
        $currentTime = $now->format('H:i:s');
        if (!empty($this->start_time) && $currentTime < $this->start_time) {
            return false;
        }
        if (!empty($this->end_time) && $currentTime > $this->end_time) {
            return false;
        }

        // 5. Days of week check (e.g. ["monday", "tuesday"])
        if (!empty($this->days_of_week) && is_array($this->days_of_week) && count($this->days_of_week) > 0) {
            $currentDay = strtolower($now->format('l'));
            if (!in_array($currentDay, array_map('strtolower', $this->days_of_week))) {
                return false;
            }
        }

        // 6. Branch restriction check (if branches configured, must be in list)
        if ($branchId && $this->branches()->exists()) {
            if (!$this->branches()->where('branches.id', $branchId)->exists()) {
                return false;
            }
        }

        // 7. Order type restriction check (if order types configured, must be in list)
        if ($orderTypeId && $this->orderTypes()->exists()) {
            if (!$this->orderTypes()->where('order_types.id', $orderTypeId)->exists()) {
                return false;
            }
        }

        // 8. Card number / BIN prefix check (for Bank / Card Discounts)
        if (!empty($this->card_numbers)) {
            if (empty($cardNumber)) {
                return false;
            }
            $cleanCard = preg_replace('/\D/', '', $cardNumber);
            $rawNumbers = preg_split('/[\r\n,]+/', $this->card_numbers, -1, PREG_SPLIT_NO_EMPTY);
            $matched = false;
            foreach ($rawNumbers as $num) {
                $cleanNum = preg_replace('/\D/', '', trim($num));
                if (!empty($cleanNum) && str_starts_with($cleanCard, $cleanNum)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount deduction amount
     * 
     * @param float $subtotal
     * @param array $items Array of items with keys: ['food_item_id', 'food_group_id', 'total_price']
     * @return float
     */
    public function calculateDiscountAmount(float $subtotal, array $items = [], float $addonAmount = 0.00): float
    {
        $eligibleAmount = $subtotal;

        // If without_addon, deduct addon total from eligible amount
        if ($this->apply_addon_timing === 'without_addon' && $addonAmount > 0) {
            $eligibleAmount = max(0, $eligibleAmount - $addonAmount);
        }

        // If scoped to category, sum only eligible items
        if ($this->scope === 'category') {
            $eligibleGroupIds = $this->foodGroups()->pluck('food_groups.id')->toArray();
            if (!empty($eligibleGroupIds)) {
                $eligibleAmount = 0;
                foreach ($items as $item) {
                    if (in_array($item['food_group_id'] ?? null, $eligibleGroupIds)) {
                        $eligibleAmount += (float)($item['total_price'] ?? 0);
                    }
                }
            }
        } elseif ($this->scope === 'item') {
            $eligibleItemIds = $this->foodItems()->pluck('food_items.id')->toArray();
            if (!empty($eligibleItemIds)) {
                $eligibleAmount = 0;
                foreach ($items as $item) {
                    if (in_array($item['food_item_id'] ?? null, $eligibleItemIds)) {
                        $eligibleAmount += (float)($item['total_price'] ?? 0);
                    }
                }
            }
        }

        if ($eligibleAmount <= 0) {
            return 0.00;
        }

        $calculated = 0.00;
        if ($this->type === 'percentage') {
            $calculated = ($eligibleAmount * (float)$this->value) / 100;
            // Cap at max_discount_amount if specified
            if (!is_null($this->max_discount_amount) && (float)$this->max_discount_amount > 0) {
                $calculated = min($calculated, (float)$this->max_discount_amount);
            }
        } else {
            // Fixed amount discount
            $calculated = min((float)$this->value, $eligibleAmount);
        }

        return round($calculated, 2);
    }
}
