<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LocationSeeder::class,
            CompanySeeder::class,
            BranchSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            FoodGroupSeeder::class,
            FoodItemSeeder::class,
            IngredientGroupSeeder::class,
            IngredientUnitSeeder::class,
            IngredientItemSeeder::class,
            PaymentTypeSeeder::class,
            PosWorkflowSeeder::class,
        ]);
    }
}
