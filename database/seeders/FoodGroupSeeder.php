<?php

namespace Database\Seeders;

use App\Models\FoodGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoodGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Burgers', 'description' => 'Various types of burgers', 'is_active' => true],
            ['name' => 'Pizzas', 'description' => 'Wood-fired pizzas', 'is_active' => true],
            ['name' => 'Beverages', 'description' => 'Cold drinks and shakes', 'is_active' => true],
            ['name' => 'Desserts', 'description' => 'Sweet items', 'is_active' => true],
            ['name' => 'Sides', 'description' => 'Fries and appetizers', 'is_active' => true],
        ];

        FoodGroup::insert($data);
    }
}
