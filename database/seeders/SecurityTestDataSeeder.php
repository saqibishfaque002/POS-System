<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SecurityTestDataSeeder extends Seeder
{
    /**
     * Coordinate execution of all security test data seeders.
     * Generates test actors across roles, active/closed shifts, orders, and inventory stock.
     */
    public function run(): void
    {
        $this->call([
            SecurityTestUserSeeder::class,
            SecurityTestWorkPeriodSeeder::class,
            SecurityTestOrderSeeder::class,
            SecurityTestTradeSeeder::class,
        ]);
    }
}
