<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\City;
use Illuminate\Database\Seeder;

class CustomerMasterSeeder extends Seeder
{
    /**
     * Seed master customer records idempotently.
     */
    public function run(): void
    {
        $city = City::where('name', 'Karachi')->first() ?? City::first();

        $customers = [
            [
                'name' => 'Dr. Salman Ahmed',
                'phone' => '0333-5554443',
                'email' => 'salman.ahmed@example.com',
                'address' => 'House 45, Street 12, Phase 6, DHA, Karachi',
                'city_id' => $city?->id,
                'customer_type' => 'regular',
                'is_active' => true,
            ],
            [
                'name' => 'Corporate Account - Shell Pakistan',
                'phone' => '0321-8889990',
                'email' => 'corporate.procurement@shell.pk',
                'address' => 'Shell House, Clifton Road, Karachi',
                'city_id' => $city?->id,
                'customer_type' => 'corporate',
                'is_active' => true,
            ],
            [
                'name' => 'Zainab Fatima',
                'phone' => '0300-9876543',
                'email' => 'zainab.f@example.com',
                'address' => 'Apartment 4B, Creek Vistas, Karachi',
                'city_id' => $city?->id,
                'customer_type' => 'vip',
                'is_active' => true,
            ],
        ];

        foreach ($customers as $cData) {
            Customer::updateOrCreate(
                ['phone' => $cData['phone']],
                $cData
            );
        }
    }
}
