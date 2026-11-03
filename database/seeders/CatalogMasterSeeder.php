<?php

namespace Database\Seeders;

use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\Variation;
use App\Models\FoodItemVariation;
use App\Models\PropertyGroup;
use App\Models\PropertyItem;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class CatalogMasterSeeder extends Seeder
{
    /**
     * Seed master food groups, variations, property addons, and food items idempotently.
     */
    public function run(): void
    {
        // 1. Food Groups
        $groups = [
            ['name' => 'Burgers', 'description' => 'Gourmet smashed and grilled burgers', 'is_active' => true],
            ['name' => 'Pizzas', 'description' => 'Neapolitan wood-fired artisan pizzas', 'is_active' => true],
            ['name' => 'Sides & Appetizers', 'description' => 'Crispy loaded fries and finger food', 'is_active' => true],
            ['name' => 'Beverages', 'description' => 'Fresh mocktails, sodas, and shakes', 'is_active' => true],
            ['name' => 'Desserts', 'description' => 'Artisan desserts and baked delicacies', 'is_active' => true],
        ];

        $groupMap = [];
        foreach ($groups as $g) {
            $groupMap[$g['name']] = FoodGroup::updateOrCreate(
                ['name' => $g['name']],
                $g
            );
        }

        // 2. Global Variations
        $variations = [
            ['name' => 'Single Patty', 'is_active' => true],
            ['name' => 'Double Patty', 'is_active' => true],
            ['name' => 'Regular (10 Inch)', 'is_active' => true],
            ['name' => 'Large (14 Inch)', 'is_active' => true],
            ['name' => 'Small', 'is_active' => true],
            ['name' => 'Large', 'is_active' => true],
        ];

        $variationMap = [];
        foreach ($variations as $v) {
            $variationMap[$v['name']] = Variation::updateOrCreate(
                ['name' => $v['name']],
                $v
            );
        }

        // 3. Property Groups & Items (Addons / Modifiers)
        $propGroups = [
            [
                'name' => 'Burger Addons & Extras',
                'is_multiple' => true,
                'items' => [
                    ['name' => 'Extra Cheddar Slice', 'extra_price' => 60.00],
                    ['name' => 'Grilled Mushrooms', 'extra_price' => 80.00],
                    ['name' => 'Pickled Jalapenos', 'extra_price' => 50.00],
                    ['name' => 'Crispy Fried Egg', 'extra_price' => 50.00],
                ]
            ],
            [
                'name' => 'Dipping Sauces',
                'is_multiple' => true,
                'items' => [
                    ['name' => 'Garlic Mayo Dip', 'extra_price' => 50.00],
                    ['name' => 'Smoked Chipotle Sauce', 'extra_price' => 60.00],
                    ['name' => 'Fiery Ghost Pepper Sauce', 'extra_price' => 70.00],
                ]
            ],
        ];

        foreach ($propGroups as $pgData) {
            $items = $pgData['items'];
            $propGroup = PropertyGroup::updateOrCreate(
                ['name' => $pgData['name']],
                ['is_multiple' => $pgData['is_multiple'], 'is_active' => true]
            );

            foreach ($items as $piData) {
                PropertyItem::updateOrCreate(
                    ['name' => $piData['name'], 'property_group_id' => $propGroup->id],
                    ['extra_price' => $piData['extra_price'], 'is_active' => true]
                );
            }
        }

        // 4. Food Items
        $foodItems = [
            [
                'name' => 'Classic Smash Beef Burger',
                'group' => 'Burgers',
                'base_price' => 550.00,
                'description' => '80/20 Angus beef patty, melted cheddar, lettuce, tomato, brioche bun',
                'variations' => [
                    ['variation' => 'Single Patty', 'sku' => 'BEEF-SGL', 'price' => 550.00],
                    ['variation' => 'Double Patty', 'sku' => 'BEEF-DBL', 'price' => 850.00],
                ],
            ],
            [
                'name' => 'Crispy Zinger Chicken Burger',
                'group' => 'Burgers',
                'base_price' => 480.00,
                'description' => 'Golden fried chicken breast fillet, spicy mayo, crisp iceberg lettuce',
                'variations' => [
                    ['variation' => 'Single Patty', 'sku' => 'CHK-SGL', 'price' => 480.00],
                    ['variation' => 'Double Patty', 'sku' => 'CHK-DBL', 'price' => 720.00],
                ],
            ],
            [
                'name' => 'Margherita Basil Pizza',
                'group' => 'Pizzas',
                'base_price' => 850.00,
                'description' => 'San Marzano tomato sauce, fresh mozzarella, aromatic basil, olive oil',
                'variations' => [
                    ['variation' => 'Regular (10 Inch)', 'sku' => 'PZA-MARG-REG', 'price' => 850.00],
                    ['variation' => 'Large (14 Inch)', 'sku' => 'PZA-MARG-LRG', 'price' => 1350.00],
                ],
            ],
            [
                'name' => 'Loaded Cheesy French Fries',
                'group' => 'Sides & Appetizers',
                'base_price' => 380.00,
                'description' => 'Golden 9mm straight cut fries drenched in warm cheddar sauce and herbs',
                'variations' => [
                    ['variation' => 'Regular (10 Inch)', 'sku' => 'FRIES-REG', 'price' => 380.00],
                    ['variation' => 'Large (14 Inch)', 'sku' => 'FRIES-LRG', 'price' => 550.00],
                ],
            ],
            [
                'name' => 'Fresh Mint Lemonade (500ml)',
                'group' => 'Beverages',
                'base_price' => 220.00,
                'description' => 'Freshly squeezed lemon juice blended with garden mint and crushed ice',
                'variations' => [],
            ],
        ];

        $branches = Branch::where('is_active', true)->get();

        foreach ($foodItems as $fiData) {
            $group = $groupMap[$fiData['group']] ?? FoodGroup::first();
            $foodItem = FoodItem::updateOrCreate(
                ['name' => $fiData['name']],
                [
                    'food_group_id' => $group->id,
                    'base_price' => $fiData['base_price'],
                    'description' => $fiData['description'],
                    'is_active' => true,
                ]
            );

            // Handle variations
            if (!empty($fiData['variations'])) {
                foreach ($fiData['variations'] as $vData) {
                    $variationModel = $variationMap[$vData['variation']] ?? null;
                    if ($variationModel) {
                        FoodItemVariation::updateOrCreate(
                            ['food_item_id' => $foodItem->id, 'variation_id' => $variationModel->id],
                            [
                                'sku' => $vData['sku'],
                                'price' => $vData['price'],
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }

            // Sync with all active branches
            foreach ($branches as $branch) {
                $foodItem->branches()->syncWithoutDetaching([
                    $branch->id => [
                        'custom_price' => $foodItem->base_price,
                        'is_active' => true,
                    ]
                ]);
            }
        }
    }
}
