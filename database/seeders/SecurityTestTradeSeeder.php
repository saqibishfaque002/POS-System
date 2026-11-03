<?php

namespace Database\Seeders;

use App\Models\GoodReceivedNote;
use App\Models\GoodReceivedNoteItem;
use App\Models\InventoryLedger;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\IngredientItem;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SecurityTestTradeSeeder extends Seeder
{
    /**
     * Seed initial inventory stock via verified GRN and central inventory ledgers idempotently.
     */
    public function run(): void
    {
        $branch = Branch::where('name', 'Downtown Branch')->first() ?? Branch::first();
        $supplier = Supplier::where('name', 'Al-Madina Meat Suppliers')->first() ?? Supplier::first();
        $manager = User::where('email', 'manager.downtown@pos.local')->first() ?? User::where('role', 'manager')->first();

        if (!$branch || !$supplier || !$manager) return;

        // Create completed stock receipt GRN
        $grn = GoodReceivedNote::updateOrCreate(
            ['document_number' => 'GRN-TEST-INIT-001'],
            [
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'date' => Carbon::now()->subDays(2)->toDateString(),
                'status' => 'completed',
                'notes' => 'Initial Inventory Stock Loading for Security and Scenario Testing',
                'created_by' => $manager->id,
            ]
        );

        $ingredientsToStock = [
            'Prime Beef Mince (80/20)' => ['qty' => 50, 'price' => 950.00],
            'Boneless Chicken Breast Fillet' => ['qty' => 40, 'price' => 750.00],
            'Cheddar Cheese Slices' => ['qty' => 30, 'price' => 650.00],
            'Grated Mozzarella Cheese' => ['qty' => 25, 'price' => 1400.00],
            'Brioche Sesame Burger Buns' => ['qty' => 50, 'price' => 300.00],
            'Artisan Pizza Dough Ball (250g)' => ['qty' => 40, 'price' => 120.00],
            'Frozen French Fries (9mm Cut)' => ['qty' => 60, 'price' => 450.00],
            'Canola Cooking Oil' => ['qty' => 50, 'price' => 550.00],
            'Iceberg Lettuce' => ['qty' => 20, 'price' => 180.00],
            'Roma Tomatoes' => ['qty' => 20, 'price' => 120.00],
            'Fresh Lemons' => ['qty' => 15, 'price' => 220.00],
            'Fresh Mint Leaves' => ['qty' => 500, 'price' => 0.50],
            'Refined White Sugar' => ['qty' => 30, 'price' => 160.00],
        ];

        // Clean existing items and ledgers for this test GRN to avoid duplicates
        $grn->items()->delete();
        InventoryLedger::where('document_type', GoodReceivedNote::class)
            ->where('document_id', $grn->id)
            ->delete();

        foreach ($ingredientsToStock as $itemName => $stockData) {
            $item = IngredientItem::where('name', $itemName)->first();
            if (!$item) continue;

            $grn->items()->create([
                'item_id' => $item->id,
                'quantity' => $stockData['qty'],
                'unit_id' => $item->purchase_unit_id,
                'unit_price' => $stockData['price'],
                'subtotal' => $stockData['qty'] * $stockData['price'],
            ]);

            // Positive stock entry in central inventory_ledgers
            InventoryLedger::create([
                'branch_id' => $branch->id,
                'item_id' => $item->id,
                'document_type' => GoodReceivedNote::class,
                'document_id' => $grn->id,
                'document_date' => $grn->date,
                'quantity' => $stockData['qty'],
                'unit_price' => $stockData['price'],
                'notes' => 'Initial testing stock via GRN ' . $grn->document_number,
                'created_by' => $manager->id,
            ]);
        }
    }
}
