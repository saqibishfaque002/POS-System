<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $country = \App\Models\Country::updateOrCreate(
            ['name' => 'Pakistan'],
            ['is_active' => true]
        );

        $province = \App\Models\Province::updateOrCreate(
            ['name' => 'Sindh'],
            ['country_id' => $country->id, 'is_active' => true]
        );

        \App\Models\City::updateOrCreate(
            ['name' => 'Karachi'],
            ['province_id' => $province->id, 'is_active' => true]
        );

        \App\Models\City::updateOrCreate(
            ['name' => 'Hyderabad'],
            ['province_id' => $province->id, 'is_active' => true]
        );
    }
}
