<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\IngredientItem;
use Illuminate\Database\Seeder;

class SupplierMasterSeeder extends Seeder
{
    /**
     * Seed master suppliers and link supplied ingredient items idempotently.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Al-Madina Meat Suppliers',
                'contact_person' => 'Muhammad Bilal',
                'phone' => '+92-300-1122334',
                'email' => 'orders@almadinameat.pk',
                'address' => 'Shop 18, Wholesale Meat Market, Karachi',
                'is_active' => true,
                'supplied_items' => ['Prime Beef Mince (80/20)', 'Boneless Chicken Breast Fillet'],
            ],
            [
                'name' => 'Fresh Valley Dairy Foods',
                'contact_person' => 'Tariq Mehmood',
                'phone' => '+92-321-4455667',
                'email' => 'sales@freshvalleydairy.pk',
                'address' => 'Plot 88, Korangi Industrial Area, Karachi',
                'is_active' => true,
                'supplied_items' => ['Cheddar Cheese Slices', 'Grated Mozzarella Cheese'],
            ],
            [
                'name' => 'Green Oasis Farm Produce',
                'contact_person' => 'Abdul Rehman',
                'phone' => '+92-333-7788990',
                'email' => 'farms@greenoasis.pk',
                'address' => 'Sabzi Mandi Super Highway, Karachi',
                'is_active' => true,
                'supplied_items' => ['Iceberg Lettuce', 'Roma Tomatoes', 'Frozen French Fries (9mm Cut)', 'Fresh Mint Leaves', 'Fresh Lemons'],
            ],
            [
                'name' => 'Crown Bakery & Milling Co.',
                'contact_person' => 'Farhan Qureshi',
                'phone' => '+92-345-9988776',
                'email' => 'supply@crownbakery.pk',
                'address' => 'Industrial Estate, Karachi',
                'is_active' => true,
                'supplied_items' => ['Brioche Sesame Burger Buns', 'Artisan Pizza Dough Ball (250g)'],
            ],
            [
                'name' => 'EcoPack Disposables Ltd.',
                'contact_person' => 'Kamran Shah',
                'phone' => '+92-302-3344556',
                'email' => 'orders@ecopack.pk',
                'address' => 'SITE Area, Karachi',
                'is_active' => true,
                'supplied_items' => ['Biodegradable Takeaway Burger Box', 'Paper Beverage Cup 500ml'],
            ],
        ];

        foreach ($suppliers as $sData) {
            $itemNames = $sData['supplied_items'];
            unset($sData['supplied_items']);

            $supplier = Supplier::updateOrCreate(
                ['name' => $sData['name']],
                $sData
            );

            // Link ingredients
            $itemIds = IngredientItem::whereIn('name', $itemNames)->pluck('id')->toArray();
            if (!empty($itemIds)) {
                $supplier->items()->syncWithoutDetaching($itemIds);
            }
        }
    }
}
