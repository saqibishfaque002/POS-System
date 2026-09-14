<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [];
        for ($i = 1; $i <= 20; $i++) {
            $products[] = [
                'name' => 'Dummy Product ' . $i,
                'sku' => 'SKU-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'price' => rand(100, 10000) / 100,
                'stock' => rand(10, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        \App\Models\Product::insert($products);
    }
}
