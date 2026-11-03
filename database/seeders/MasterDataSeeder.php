<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Coordinate execution of all master data seeders idempotently.
     * Contains only static, reference, configuration, and catalog master records.
     * Kept strictly separated from transactional, shift, order, and test actor data.
     */
    public function run(): void
    {
        $this->call([
            LocationMasterSeeder::class,
            CompanyAndBranchMasterSeeder::class,
            SettingMasterSeeder::class,
            OrderTypeAndPaymentTypeMasterSeeder::class,
            PartyMasterSeeder::class,
            IngredientMasterSeeder::class,
            SupplierMasterSeeder::class,
            CatalogMasterSeeder::class,
            RecipeMasterSeeder::class,
            MenuMasterSeeder::class,
            DiscountMasterSeeder::class,
            CustomerMasterSeeder::class,
        ]);
    }
}
