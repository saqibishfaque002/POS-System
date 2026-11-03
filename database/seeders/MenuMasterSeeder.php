<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Branch;
use App\Models\FoodItem;
use Illuminate\Database\Seeder;

class MenuMasterSeeder extends Seeder
{
    /**
     * Seed master menus, branch mappings, and food item pricing idempotently.
     */
    public function run(): void
    {
        $menusData = [
            [
                'name' => 'Dine-in A La Carte Menu',
                'tax_percentage' => 13.00,
                'discount_percentage' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'Express Delivery Menu',
                'tax_percentage' => 13.00,
                'discount_percentage' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'Midnight Takeaway Deals',
                'tax_percentage' => 13.00,
                'discount_percentage' => 5.00,
                'is_active' => true,
            ],
        ];

        $branches = Branch::where('is_active', true)->get();
        $foodItems = FoodItem::where('is_active', true)->get();

        foreach ($menusData as $mData) {
            $menu = Menu::updateOrCreate(
                ['name' => $mData['name']],
                $mData
            );

            // Sync with all active branches
            if ($branches->isNotEmpty()) {
                $menu->branches()->syncWithoutDetaching($branches->pluck('id')->toArray());
            }

            // Sync with all active food items
            foreach ($foodItems as $item) {
                $price = $item->base_price;
                // Midnight menu gives a promotional price
                if ($menu->name === 'Midnight Takeaway Deals') {
                    $price = round($item->base_price * 0.90, 2); // 10% lower pricing
                }

                $menu->items()->syncWithoutDetaching([
                    $item->id => ['price' => $price]
                ]);
            }
        }
    }
}
