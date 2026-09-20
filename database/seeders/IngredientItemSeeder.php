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

        $unit = \App\Models\IngredientUnit::firstOrCreate(
            ['name' => 'Kilogram'],
            ['short_code' => 'KG', 'is_active' => true]
        );

        $data = [
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $unit->id, 'consumption_unit_id' => $unit->id, 'conversion_qty' => 1, 'name' => 'Onion', 'base_price' => 150, 'description' => 'Red onions', 'is_active' => true],
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $unit->id, 'consumption_unit_id' => $unit->id, 'conversion_qty' => 1, 'name' => 'Tomato', 'base_price' => 100, 'description' => 'Fresh tomatoes', 'is_active' => true],
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $unit->id, 'consumption_unit_id' => $unit->id, 'conversion_qty' => 1, 'name' => 'Potato', 'base_price' => 80, 'description' => 'Potatoes', 'is_active' => true],
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $unit->id, 'consumption_unit_id' => $unit->id, 'conversion_qty' => 1, 'name' => 'Cabbage', 'base_price' => 120, 'description' => 'Fresh cabbage', 'is_active' => true],
            ['ingredient_group_id' => $group->id, 'purchase_unit_id' => $unit->id, 'consumption_unit_id' => $unit->id, 'conversion_qty' => 1, 'name' => 'Carrot', 'base_price' => 90, 'description' => 'Fresh carrots', 'is_active' => true],
            
        ];

        IngredientItem::insert($data);
    }
}
