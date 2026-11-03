<?php

namespace Database\Seeders;

use App\Models\IngredientUnit;
use App\Models\IngredientGroup;
use App\Models\IngredientItem;
use Illuminate\Database\Seeder;

class IngredientMasterSeeder extends Seeder
{
    /**
     * Seed master ingredient units, groups, and items idempotently.
     */
    public function run(): void
    {
        // 1. Units
        $units = [
            ['name' => 'Kilogram', 'short_code' => 'KG', 'is_active' => true],
            ['name' => 'Gram', 'short_code' => 'G', 'is_active' => true],
            ['name' => 'Liter', 'short_code' => 'L', 'is_active' => true],
            ['name' => 'Milliliter', 'short_code' => 'ML', 'is_active' => true],
            ['name' => 'Piece', 'short_code' => 'PCS', 'is_active' => true],
            ['name' => 'Box', 'short_code' => 'BOX', 'is_active' => true],
            ['name' => 'Packet', 'short_code' => 'PKT', 'is_active' => true],
            ['name' => 'Portion', 'short_code' => 'PORT', 'is_active' => true],
        ];

        $unitMap = [];
        foreach ($units as $u) {
            $unitModel = IngredientUnit::updateOrCreate(
                ['name' => $u['name']],
                $u
            );
            $unitMap[$u['short_code']] = $unitModel;
        }

        // 2. Groups
        $groups = [
            ['name' => 'Meat & Poultry', 'description' => 'Fresh beef, chicken, mutton', 'is_active' => true],
            ['name' => 'Dairy & Eggs', 'description' => 'Cheese, butter, fresh milk, farm eggs', 'is_active' => true],
            ['name' => 'Produce & Fresh Herbs', 'description' => 'Vegetables, herbs, and fruits', 'is_active' => true],
            ['name' => 'Bakery & Dough', 'description' => 'Buns, bread, pizza dough', 'is_active' => true],
            ['name' => 'Oils, Sauces & Spices', 'description' => 'Cooking oils, sauces, dry seasonings', 'is_active' => true],
            ['name' => 'Beverages & Syrups', 'description' => 'Beverage ingredients, sodas, concentrates', 'is_active' => true],
            ['name' => 'Packaging & Disposables', 'description' => 'Boxes, cups, napkins, bags', 'is_active' => true],
        ];

        $groupMap = [];
        foreach ($groups as $g) {
            $groupModel = IngredientGroup::updateOrCreate(
                ['name' => $g['name']],
                $g
            );
            $groupMap[$g['name']] = $groupModel;
        }

        // 3. Items
        $items = [
            [
                'name' => 'Prime Beef Mince (80/20)',
                'group' => 'Meat & Poultry',
                'purchase_unit' => 'KG',
                'consumption_unit' => 'G',
                'conversion_qty' => 1000,
                'base_price' => 950.00,
                'reorder_level' => 10,
                'description' => 'High quality 80/20 lean beef mince for burger patties',
            ],
            [
                'name' => 'Boneless Chicken Breast Fillet',
                'group' => 'Meat & Poultry',
                'purchase_unit' => 'KG',
                'consumption_unit' => 'G',
                'conversion_qty' => 1000,
                'base_price' => 750.00,
                'reorder_level' => 15,
                'description' => 'Fresh skinless boneless chicken breast fillets',
            ],
            [
                'name' => 'Cheddar Cheese Slices',
                'group' => 'Dairy & Eggs',
                'purchase_unit' => 'PKT',
                'consumption_unit' => 'PCS',
                'conversion_qty' => 20,
                'base_price' => 650.00,
                'reorder_level' => 5,
                'description' => 'Burger sliced cheddar cheese pack (20 slices)',
            ],
            [
                'name' => 'Grated Mozzarella Cheese',
                'group' => 'Dairy & Eggs',
                'purchase_unit' => 'KG',
                'consumption_unit' => 'G',
                'conversion_qty' => 1000,
                'base_price' => 1400.00,
                'reorder_level' => 8,
                'description' => 'Premium shredded mozzarella for pizzas and toppings',
            ],
            [
                'name' => 'Brioche Sesame Burger Buns',
                'group' => 'Bakery & Dough',
                'purchase_unit' => 'PKT',
                'consumption_unit' => 'PCS',
                'conversion_qty' => 6,
                'base_price' => 300.00,
                'reorder_level' => 20,
                'description' => 'Soft butter brioche buns topped with sesame seeds (6 pcs)',
            ],
            [
                'name' => 'Artisan Pizza Dough Ball (250g)',
                'group' => 'Bakery & Dough',
                'purchase_unit' => 'PCS',
                'consumption_unit' => 'PCS',
                'conversion_qty' => 1,
                'base_price' => 120.00,
                'reorder_level' => 25,
                'description' => 'Pre-fermented 250g sourdough pizza dough ball',
            ],
            [
                'name' => 'Canola Cooking Oil',
                'group' => 'Oils, Sauces & Spices',
                'purchase_unit' => 'L',
                'consumption_unit' => 'ML',
                'conversion_qty' => 1000,
                'base_price' => 550.00,
                'reorder_level' => 20,
                'description' => 'Pure refined canola oil for deep frying',
            ],
            [
                'name' => 'Iceberg Lettuce',
                'group' => 'Produce & Fresh Herbs',
                'purchase_unit' => 'KG',
                'consumption_unit' => 'G',
                'conversion_qty' => 1000,
                'base_price' => 180.00,
                'reorder_level' => 5,
                'description' => 'Crisp fresh hydroponic iceberg lettuce',
            ],
            [
                'name' => 'Roma Tomatoes',
                'group' => 'Produce & Fresh Herbs',
                'purchase_unit' => 'KG',
                'consumption_unit' => 'G',
                'conversion_qty' => 1000,
                'base_price' => 120.00,
                'reorder_level' => 5,
                'description' => 'Firm ripe sliced tomatoes',
            ],
            [
                'name' => 'Frozen French Fries (9mm Cut)',
                'group' => 'Produce & Fresh Herbs',
                'purchase_unit' => 'KG',
                'consumption_unit' => 'G',
                'conversion_qty' => 1000,
                'base_price' => 450.00,
                'reorder_level' => 15,
                'description' => 'Imported 9mm straight cut frozen potatoes',
            ],
            [
                'name' => 'Fresh Mint Leaves',
                'group' => 'Produce & Fresh Herbs',
                'purchase_unit' => 'G',
                'consumption_unit' => 'G',
                'conversion_qty' => 1,
                'base_price' => 0.50,
                'reorder_level' => 200,
                'description' => 'Fresh garden mint for lemonade and garnishes',
            ],
            [
                'name' => 'Fresh Lemons',
                'group' => 'Produce & Fresh Herbs',
                'purchase_unit' => 'KG',
                'consumption_unit' => 'PCS',
                'conversion_qty' => 15,
                'base_price' => 220.00,
                'reorder_level' => 3,
                'description' => 'Juicy seedless lemons',
            ],
            [
                'name' => 'Refined White Sugar',
                'group' => 'Oils, Sauces & Spices',
                'purchase_unit' => 'KG',
                'consumption_unit' => 'G',
                'conversion_qty' => 1000,
                'base_price' => 160.00,
                'reorder_level' => 10,
                'description' => 'Fine grain white sugar',
            ],
            [
                'name' => 'Biodegradable Takeaway Burger Box',
                'group' => 'Packaging & Disposables',
                'purchase_unit' => 'PCS',
                'consumption_unit' => 'PCS',
                'conversion_qty' => 1,
                'base_price' => 18.00,
                'reorder_level' => 100,
                'description' => 'Corrugated kraft takeaway burger box',
            ],
            [
                'name' => 'Paper Beverage Cup 500ml',
                'group' => 'Packaging & Disposables',
                'purchase_unit' => 'PCS',
                'consumption_unit' => 'PCS',
                'conversion_qty' => 1,
                'base_price' => 15.00,
                'reorder_level' => 100,
                'description' => 'Cold drink paper cups with sipper lids',
            ],
        ];

        foreach ($items as $item) {
            $group = $groupMap[$item['group']] ?? IngredientGroup::first();
            $purchaseUnit = $unitMap[$item['purchase_unit']] ?? IngredientUnit::first();
            $consumptionUnit = $unitMap[$item['consumption_unit']] ?? IngredientUnit::first();

            IngredientItem::updateOrCreate(
                ['name' => $item['name']],
                [
                    'ingredient_group_id' => $group->id,
                    'purchase_unit_id' => $purchaseUnit->id,
                    'consumption_unit_id' => $consumptionUnit->id,
                    'conversion_qty' => $item['conversion_qty'],
                    'base_price' => $item['base_price'],
                    'reorder_level' => $item['reorder_level'],
                    'description' => $item['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
