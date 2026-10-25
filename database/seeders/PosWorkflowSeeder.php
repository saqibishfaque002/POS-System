<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\OrderType;
use App\Models\Party;
use App\Models\City;
use App\Models\Menu;
use App\Models\FoodItem;
use App\Models\Floor;
use App\Models\Table;
use App\Models\Customer;
use App\Models\PaymentType;
use App\Models\Discount;

class PosWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Ensures idempotent test and workflow data for POS Terminal.
     */
    public function run(): void
    {
        $city = City::first();
        $cityId = $city ? $city->id : null;

        // 1. Order Types
        $dineIn = OrderType::firstOrCreate(
            ['code' => 'dine_in'],
            ['name' => 'Dine-in', 'service_charge_percent' => 0.00, 'is_active' => true]
        );
        $takeaway = OrderType::firstOrCreate(
            ['code' => 'takeaway'],
            ['name' => 'Takeaway', 'service_charge_percent' => 0.00, 'is_active' => true]
        );
        $delivery = OrderType::firstOrCreate(
            ['code' => 'delivery'],
            ['name' => 'Delivery', 'service_charge_percent' => 0.00, 'is_active' => true]
        );

        // 2. Realistic Parties
        $foodpanda = Party::firstOrCreate(
            ['name' => 'Foodpanda'],
            [
                'party_type' => 'delivery_partner',
                'phone' => '021-111366372',
                'email' => 'partner@foodpanda.pk',
                'address' => 'Foodpanda Pakistan HQ',
                'city_id' => $cityId,
                'is_active' => true,
            ]
        );

        $inHouseDelivery = Party::firstOrCreate(
            ['name' => 'In-House Delivery'],
            [
                'party_type' => 'delivery_partner',
                'phone' => '0300-1234567',
                'email' => 'delivery@restaurant.com',
                'address' => 'Downtown Express Fleet',
                'city_id' => $cityId,
                'is_active' => true,
            ]
        );

        $careem = Party::firstOrCreate(
            ['name' => 'Careem Now'],
            [
                'party_type' => 'delivery_partner',
                'phone' => '021-111227336',
                'email' => 'fleet@careem.pk',
                'address' => 'Careem Operations Center',
                'city_id' => $cityId,
                'is_active' => true,
            ]
        );

        $walkInCounter = Party::firstOrCreate(
            ['name' => 'Walk-in Counter'],
            [
                'party_type' => 'customer',
                'phone' => '0300-0000000',
                'address' => 'Front Counter / Direct Walk-in',
                'city_id' => $cityId,
                'is_active' => true,
            ]
        );

        $driveThru = Party::firstOrCreate(
            ['name' => 'Drive-Thru'],
            [
                'party_type' => 'customer',
                'phone' => '0300-0000001',
                'address' => 'Drive-Thru Lane 1',
                'city_id' => $cityId,
                'is_active' => true,
            ]
        );

        $inHouseDining = Party::firstOrCreate(
            ['name' => 'In-House Dining'],
            [
                'party_type' => 'customer',
                'phone' => '0300-0000002',
                'address' => 'Main Dining Hall',
                'city_id' => $cityId,
                'is_active' => true,
            ]
        );

        // 3. Link Order Types to Parties (Idempotent syncWithoutDetaching)
        $dineIn->parties()->syncWithoutDetaching([
            $inHouseDining->id,
        ]);

        $takeaway->parties()->syncWithoutDetaching([
            $walkInCounter->id,
            $driveThru->id,
            $foodpanda->id,
        ]);

        $delivery->parties()->syncWithoutDetaching([
            $foodpanda->id,
            $inHouseDelivery->id,
            $careem->id,
        ]);

        // 4. Menu & Item Associations
        $menu = Menu::firstOrCreate(
            ['name' => 'All Day Dining Menu'],
            [
                'tax_percentage' => 16.00,
                'discount_percentage' => 0.00,
                'is_active' => true,
            ]
        );

        $branches = Branch::where('is_active', true)->get();
        $foodItems = FoodItem::where('is_active', true)->get();

        foreach ($branches as $branch) {
            // Attach menu to branch
            $menu->branches()->syncWithoutDetaching([$branch->id]);

            // Attach food items to branch
            foreach ($foodItems as $item) {
                DB::table('branch_food_item')->updateOrInsert(
                    ['branch_id' => $branch->id, 'food_item_id' => $item->id],
                    ['is_active' => true, 'custom_price' => null, 'updated_at' => now()]
                );
            }

            // Floors and Tables
            $floor = Floor::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => 'Ground Floor'],
                ['is_active' => true]
            );

            for ($i = 1; $i <= 6; $i++) {
                Table::firstOrCreate(
                    ['floor_id' => $floor->id, 'name' => 'Table ' . $i],
                    ['capacity' => 4, 'is_active' => true]
                );
            }
        }

        // Attach food items to menu
        foreach ($foodItems as $item) {
            $menu->items()->syncWithoutDetaching([
                $item->id => ['price' => null, 'created_at' => now(), 'updated_at' => now()]
            ]);
        }

        // 5. Realistic Test Customers
        $customers = [
            [
                'name' => 'Muhammad Ali',
                'phone' => '0300-1122334',
                'email' => 'ali@example.com',
                'address' => 'House 42, Block 6, PECHS, Karachi',
                'city_id' => $cityId,
                'is_active' => true,
            ],
            [
                'name' => 'Fatima Noor',
                'phone' => '0312-9876543',
                'email' => 'fatima@example.com',
                'address' => 'Apartment 5B, Clifton Block 4, Karachi',
                'city_id' => $cityId,
                'is_active' => true,
            ],
            [
                'name' => 'Zainab Bibi',
                'phone' => '0321-4455667',
                'email' => 'zainab@example.com',
                'address' => 'Plot B-14, Gulshan-e-Iqbal, Karachi',
                'city_id' => $cityId,
                'is_active' => true,
            ],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(['phone' => $c['phone']], $c);
        }

        // 6. Branch Charges (Delivery Charges & Service Charges)
        foreach ($branches as $b) {
            $b->delivery_charges = 150.00;
            $b->service_charge_type = 'percentage';
            $b->service_charge_value = 5.00;
            $b->save();
        }

        // 7. Payment Types (Cash, Card, FOC / Complimentary)
        $cashPt = PaymentType::firstOrCreate(['name' => 'Cash'], ['is_active' => true]);
        $cardPt = PaymentType::firstOrCreate(['name' => 'Credit Card'], ['is_active' => true]);
        $focPt = PaymentType::firstOrCreate(['name' => 'FOC / Complimentary'], ['is_active' => true]);

        foreach ($branches as $branch) {
            $branch->paymentTypes()->syncWithoutDetaching([
                $cashPt->id => ['tax_percentage' => 16.00],
                $cardPt->id => ['tax_percentage' => 16.00],
                $focPt->id => ['tax_percentage' => 0.00],
            ]);
        }

        // 8. Promotional Deals & Vouchers
        $welcome10 = Discount::firstOrCreate(
            ['name' => 'Welcome Discount (10%)'],
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_amount' => 0.00,
                'max_discount_amount' => 500.00,
                'scope' => 'cart',
                'valid_from' => now()->subDays(5),
                'valid_to' => now()->addDays(90),
                'is_bank_discount' => false,
                'is_active' => true,
            ]
        );

        $flat100 = Discount::firstOrCreate(
            ['name' => 'Flat Rs. 100 Off (Min 500)'],
            [
                'code' => 'FLAT100',
                'type' => 'fixed',
                'value' => 100.00,
                'min_order_amount' => 500.00,
                'max_discount_amount' => 100.00,
                'scope' => 'cart',
                'valid_from' => now()->subDays(5),
                'valid_to' => now()->addDays(90),
                'is_bank_discount' => false,
                'is_active' => true,
            ]
        );

        $weekend20 = Discount::firstOrCreate(
            ['name' => 'Store Special 20% Off'],
            [
                'code' => 'SAVE20',
                'type' => 'percentage',
                'value' => 20.00,
                'min_order_amount' => 0.00,
                'max_discount_amount' => 1000.00,
                'scope' => 'cart',
                'valid_from' => now()->subDays(5),
                'valid_to' => now()->addDays(90),
                'is_bank_discount' => false,
                'is_active' => true,
            ]
        );

        $allOrderTypeIds = OrderType::where('is_active', true)->pluck('id')->toArray();
        $allBranchIds = Branch::where('is_active', true)->pluck('id')->toArray();

        foreach ([$welcome10, $flat100, $weekend20] as $discount) {
            $discount->branches()->syncWithoutDetaching($allBranchIds);
            $discount->orderTypes()->syncWithoutDetaching($allOrderTypeIds);
        }
    }
}
