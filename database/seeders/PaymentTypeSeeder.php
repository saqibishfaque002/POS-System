<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Cash'],
            ['name' => 'Credit Card'],
            ['name' => 'Debit Card'],
            ['name' => 'Bank Transfer'],
            ['name' => 'Mobile Payment'],
            ['name' => 'Cryptocurrency'],
        ];
        PaymentType::insert($data);
    }
}
