<?php

namespace Database\Seeders;

use App\Models\FoodItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoodItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $group = \App\Models\FoodGroup::firstOrCreate(
            ['name' => 'Burgers'],
            ['description' => 'Various types of burgers', 'is_active' => true]
        );

        $data = [
            ['food_group_id' => $group->id, 'name' => 'Classic Beef Burger', 'base_price' => 500, 'description' => 'Juicy beef patty', 'is_active' => true],
            ['food_group_id' => $group->id, 'name' => 'Chicken Zinger', 'base_price' => 450, 'description' => 'Crispy fried chicken', 'is_active' => true],
            ['food_group_id' => $group->id, 'name' => 'Fish Burger', 'base_price' => 400, 'description' => 'Fried fish fillet', 'is_active' => true],
            ['food_group_id' => $group->id, 'name' => 'Double Patty Beef', 'base_price' => 750, 'description' => 'Double beef layers', 'is_active' => true],
            ['food_group_id' => $group->id, 'name' => 'Veggie Burger', 'base_price' => 300, 'description' => 'Plant-based patty', 'is_active' => true],
        ];

        FoodItem::insert($data);
    }
}
