<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\IngredientUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = \App\Models\Company::first();
        $city = \App\Models\City::first();

        if (!$company || !$city) {
            return; // Needs company and city to be seeded first
        }

        $data = [
            ['company_id' => $company->id, 'city_id' => $city->id, 'name' => 'Downtown Branch', 'is_active' => true],
            ['company_id' => $company->id, 'city_id' => $city->id, 'name' => 'Uptown Branch', 'is_active' => true],
            ['company_id' => $company->id, 'city_id' => $city->id, 'name' => 'Suburban Branch', 'is_active' => true],
        ];

        foreach ($data as $item) {
            \App\Models\Branch::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
