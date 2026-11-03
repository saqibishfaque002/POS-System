<?php

namespace Database\Seeders;

use App\Models\Party;
use App\Models\City;
use App\Models\OrderType;
use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PartyMasterSeeder extends Seeder
{
    /**
     * Seed master delivery and fulfillment parties idempotently.
     */
    public function run(): void
    {
        $city = City::first();
        $cityId = $city ? $city->id : null;

        $partiesData = [
            [
                'name' => 'Foodpanda Pakistan',
                'party_type' => 'delivery_partner',
                'phone' => '021-111366372',
                'email' => 'partner@foodpanda.pk',
                'address' => 'Foodpanda Pakistan HQ, Karachi',
                'city_id' => $cityId,
                'is_active' => true,
                'order_type_codes' => ['delivery'],
            ],
            [
                'name' => 'Careem Delivery Fleet',
                'party_type' => 'delivery_partner',
                'phone' => '021-111227336',
                'email' => 'fleet@careem.pk',
                'address' => 'Careem Logistics Center, Karachi',
                'city_id' => $cityId,
                'is_active' => true,
                'order_type_codes' => ['delivery'],
            ],
            [
                'name' => 'In-House Express Delivery',
                'party_type' => 'delivery_partner',
                'phone' => '0300-1234567',
                'email' => 'dispatch@gourmetbistro.pk',
                'address' => 'Central Dispatch Rider Hub, Karachi',
                'city_id' => $cityId,
                'is_active' => true,
                'order_type_codes' => ['delivery'],
            ],
            [
                'name' => 'Walk-in Counter Customer',
                'party_type' => 'customer',
                'phone' => '0300-0000000',
                'email' => 'counter@gourmetbistro.pk',
                'address' => 'Front POS Terminal Counter',
                'city_id' => $cityId,
                'is_active' => true,
                'order_type_codes' => ['takeaway', 'dine_in'],
            ],
            [
                'name' => 'Drive-Thru Lane 1',
                'party_type' => 'customer',
                'phone' => '0300-0000001',
                'email' => 'drivethru@gourmetbistro.pk',
                'address' => 'Drive-Thru Lane 1 Terminal',
                'city_id' => $cityId,
                'is_active' => true,
                'order_type_codes' => ['drive_thru', 'takeaway'],
            ],
        ];

        foreach ($partiesData as $pData) {
            $orderTypeCodes = $pData['order_type_codes'];
            unset($pData['order_type_codes']);

            $party = Party::firstOrCreate(
                ['name' => $pData['name']],
                $pData
            );

            // Link order types
            $orderTypeIds = OrderType::whereIn('code', $orderTypeCodes)->pluck('id')->toArray();
            if (!empty($orderTypeIds)) {
                $party->orderTypes()->syncWithoutDetaching($orderTypeIds);
            }

            // Link default payment types (Cash and Credit Card)
            $paymentTypeIds = PaymentType::whereIn('name', ['Cash', 'Credit Card'])->pluck('id')->toArray();
            if (!empty($paymentTypeIds)) {
                $party->paymentTypes()->syncWithoutDetaching($paymentTypeIds);
            }
        }
    }
}
