<?php

namespace Database\Seeders;

use App\Models\IngredientItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IngredientItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $group = \App\Models\IngredientGroup::firstOrCreate(
            ['name' => 'Vegetables'],
            ['description' => 'Fresh veggies', 'is_active' => true]
        );

        $kg = \App\Models\IngredientUnit::firstOrCreate(['name' => 'Kilogram'], ['short_code' => 'KG', 'is_active' => true]);
        $g = \App\Models\IngredientUnit::firstOrCreate(['name' => 'Gram'], ['short_code' => 'G', 'is_active' => true]);
        $l = \App\Models\IngredientUnit::firstOrCreate(['name' => 'Liter'], ['short_code' => 'L', 'is_active' => true]);
        $ml = \App\Models\IngredientUnit::firstOrCreate(['name' => 'Milliliter'], ['short_code' => 'ML', 'is_active' => true]);
        $pcs = \App\Models\IngredientUnit::firstOrCreate(['name' => 'Piece'], ['short_code' => 'PCS', 'is_active' => true]);

        $data = [
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $kg->id, 'consumption_unit_id' => $kg->id, 'conversion_qty' => 1, 'name' => 'Onion', 'base_price' => 150, 'description' => 'Red onions', 'is_active' => true],
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $g->id, 'consumption_unit_id' => $g->id, 'conversion_qty' => 1, 'name' => 'Saffron', 'base_price' => 5000, 'description' => 'Premium saffron', 'is_active' => true],
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $l->id, 'consumption_unit_id' => $l->id, 'conversion_qty' => 1, 'name' => 'Milk', 'base_price' => 200, 'description' => 'Fresh Milk', 'is_active' => true],
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $ml->id, 'consumption_unit_id' => $ml->id, 'conversion_qty' => 1, 'name' => 'Vanilla Extract', 'base_price' => 1500, 'description' => 'Vanilla essence', 'is_active' => true],
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $pcs->id, 'consumption_unit_id' => $pcs->id, 'conversion_qty' => 1, 'name' => 'Egg', 'base_price' => 30, 'description' => 'Farm eggs', 'is_active' => true],
        ];

        foreach ($data as $item) {
            \App\Models\IngredientItem::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
