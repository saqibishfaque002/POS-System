<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Seeder;

class LocationMasterSeeder extends Seeder
{
    /**
     * Seed master countries, provinces, and cities idempotently.
     */
    public function run(): void
    {
        $pakistan = Country::updateOrCreate(
            ['name' => 'Pakistan'],
            ['is_active' => true]
        );

        $sindh = Province::updateOrCreate(
            ['name' => 'Sindh', 'country_id' => $pakistan->id],
            ['is_active' => true]
        );

        $punjab = Province::updateOrCreate(
            ['name' => 'Punjab', 'country_id' => $pakistan->id],
            ['is_active' => true]
        );

        $ict = Province::updateOrCreate(
            ['name' => 'Islamabad Capital Territory', 'country_id' => $pakistan->id],
            ['is_active' => true]
        );

        $cities = [
            ['name' => 'Karachi', 'province_id' => $sindh->id],
            ['name' => 'Hyderabad', 'province_id' => $sindh->id],
            ['name' => 'Lahore', 'province_id' => $punjab->id],
            ['name' => 'Rawalpindi', 'province_id' => $punjab->id],
            ['name' => 'Islamabad', 'province_id' => $ict->id],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(
                ['name' => $city['name'], 'province_id' => $city['province_id']],
                ['is_active' => true]
            );
        }
    }
}
