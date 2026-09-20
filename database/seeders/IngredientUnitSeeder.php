<?php

namespace Database\Seeders;

use App\Models\IngredientUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IngredientUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Kilogram', 'short_code' => 'KG', 'is_active' => true],
            ['name' => 'Gram', 'short_code' => 'G', 'is_active' => true],
            ['name' => 'Liter', 'short_code' => 'L', 'is_active' => true],
            ['name' => 'Milliliter', 'short_code' => 'ML', 'is_active' => true],
            ['name' => 'Piece', 'short_code' => 'PCS', 'is_active' => true],
            ['name' => 'Box', 'short_code' => 'BOX', 'is_active' => true],
            ['name' => 'Packet', 'short_code' => 'pkt', 'is_active' => true],
            ['name' => 'Dozen', 'short_code' => 'DOZ', 'is_active' => true],
        ];

        IngredientUnit::insert($data);
    }
}
