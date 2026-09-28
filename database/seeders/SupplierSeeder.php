<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'ABC Distributors', 'contact_person' => 'Ali', 'phone' => '03001234567', 'email' => 'abc@example.com', 'address' => 'Karachi', 'is_active' => true],
            ['name' => 'XYZ Foods', 'contact_person' => 'Usman', 'phone' => '03007654321', 'email' => 'xyz@example.com', 'address' => 'Hyderabad', 'is_active' => true],
        ];

        foreach ($data as $item) {
            \App\Models\Supplier::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
