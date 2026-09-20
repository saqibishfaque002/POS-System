<?php

namespace Database\Seeders;

use App\Models\IngredientGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IngredientGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Vegetables', 'description' => 'Fresh veggies', 'is_active' => true],
            ['name' => 'Meats', 'description' => 'Chicken, Beef, etc.', 'is_active' => true],
            ['name' => 'Dairy', 'description' => 'Milk, Cheese, Butter', 'is_active' => true],
            ['name' => 'Spices', 'description' => 'Dry spices', 'is_active' => true],
            ['name' => 'Packaging', 'description' => 'Boxes and cups', 'is_active' => true],
            ['name' => 'Beverages', 'description' => 'Soft drinks, juices, etc.', 'is_active' => true],
            ['name' => 'Fruits', 'description' => 'Fresh fruits', 'is_active' => true],
            ['name' => 'Grains', 'description' => 'Rice, Wheat, etc.', 'is_active' => true],
            ['name' => 'Seafood', 'description' => 'Fish and other seafood', 'is_active' => true],
            ['name' => 'Frozen Foods', 'description' => 'Frozen vegetables and meats', 'is_active' => true],
        ];

        IngredientGroup::insert($data);
    }
}
