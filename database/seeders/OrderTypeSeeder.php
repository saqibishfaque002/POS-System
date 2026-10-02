<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['Dine-in', 'Takeaway', 'Delivery'];
        foreach ($types as $type) {
            \App\Models\OrderType::firstOrCreate(['name' => $type]);
        }
    }
}
