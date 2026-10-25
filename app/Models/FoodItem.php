<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    use HasFactory;

    protected $fillable = ['food_group_id', 'name', 'base_price', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
    ];

    public function foodGroup()
    {
        return $this->belongsTo(FoodGroup::class);
    }

    public function recipe()
    {
        return $this->morphOne(Recipe::class, 'recipeable');
    }

    public function variations()
    {
        return $this->hasMany(FoodItemVariation::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_food_item')
            ->withPivot(['custom_price', 'is_active'])
            ->withTimestamps();
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'food_item_menu')
            ->withPivot('price')
            ->withTimestamps();
    }

    /**
     * Scope to filter items by Branch and Menu for POS Terminal
     */
    public function scopeForBranchAndMenu($query, $branchId, $menuId)
    {
        return $query->where('food_items.is_active', true)
            ->whereHas('menus', function ($q) use ($menuId) {
                $q->where('menus.id', $menuId)
                  ->where('menus.is_active', true);
            })
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId)
                  ->where('branches.is_active', true)
                  ->where('branch_food_item.is_active', true);
            })
            ->with([
                'foodGroup:id,name',
                'variations.variation',
                'menus' => function ($q) use ($menuId) {
                    $q->where('menus.id', $menuId);
                },
                'branches' => function ($q) use ($branchId) {
                    $q->where('branches.id', $branchId);
                }
            ]);
    }

    /**
     * Calculate effective price taking into account menu and branch overrides
     */
    public function getEffectivePriceFor($branchId = null, $menuId = null): float
    {
        // 1. Menu specific price override if set
        if ($menuId) {
            $menu = $this->menus ? $this->menus->firstWhere('id', $menuId) : null;
            if ($menu && $menu->pivot && !is_null($menu->pivot->price) && (float)$menu->pivot->price > 0) {
                return (float)$menu->pivot->price;
            }
        }

        // 2. Branch custom price override if set
        if ($branchId) {
            $branch = $this->branches ? $this->branches->firstWhere('id', $branchId) : null;
            if ($branch && $branch->pivot && !is_null($branch->pivot->custom_price) && (float)$branch->pivot->custom_price > 0) {
                return (float)$branch->pivot->custom_price;
            }
        }

        // 3. Base price fallback
        return (float)($this->base_price ?? 0);
    }
}
