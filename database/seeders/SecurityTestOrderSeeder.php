<?php

namespace Database\Seeders;

use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosOrderPayment;
use App\Models\Branch;
use App\Models\User;
use App\Models\FoodItem;
use App\Models\OrderType;
use App\Models\PaymentType;
use App\Models\Table;
use App\Models\Customer;
use App\Models\PosBusinessDay;
use App\Models\PosShift;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SecurityTestOrderSeeder extends Seeder
{
    /**
     * Seed realistic POS orders across multiple lifecycle statuses idempotently.
     */
    public function run(): void
    {
        $branch = Branch::where('name', 'Downtown Branch')->first() ?? Branch::first();
        $cashier = User::where('email', 'cashier.downtown@pos.local')->first() ?? User::first();
        $burger = FoodItem::where('name', 'Classic Smash Beef Burger')->first() ?? FoodItem::first();
        $pizza = FoodItem::where('name', 'Margherita Basil Pizza')->first() ?? FoodItem::first();
        $fries = FoodItem::where('name', 'Loaded Cheesy French Fries')->first() ?? FoodItem::first();
        $dineIn = OrderType::where('code', 'dine_in')->first() ?? OrderType::first();
        $takeaway = OrderType::where('code', 'takeaway')->first() ?? OrderType::first();
        $cashPayment = PaymentType::where('name', 'Cash')->first() ?? PaymentType::first();
        $table = Table::first();
        $customer = Customer::first();
        $businessDay = PosBusinessDay::where('branch_id', $branch->id)->where('status', 'open')->first();
        $shift = PosShift::where('branch_id', $branch->id)->where('status', 'open')->first();

        if (!$branch || !$cashier || !$burger) return;

        // 1. Open Dining Order on Table (Status: pending, payment: unpaid)
        $order1 = PosOrder::updateOrCreate(
            ['order_number' => 'ORD-TEST-0001'],
            [
                'token_number' => 'T-001',
                'branch_id' => $branch->id,
                'user_id' => $cashier->id,
                'pos_business_day_id' => $businessDay?->id,
                'pos_shift_id' => $shift?->id,
                'order_type_id' => $dineIn->id,
                'table_id' => $table?->id,
                'customer_id' => $customer?->id,
                'subtotal' => 1100.00,
                'tax_amount' => 143.00,
                'grand_total' => 1243.00,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'notes' => 'Table Dining Order - Guest currently dining',
            ]
        );
        $order1->items()->delete();
        $order1->items()->create([
            'food_item_id' => $burger->id,
            'quantity' => 2,
            'unit_price' => $burger->base_price,
            'total_price' => $burger->base_price * 2,
            'notes' => 'Well done patties',
        ]);

        // 2. Submitted Kitchen Order (Status: submitted, KOT printed)
        $order2 = PosOrder::updateOrCreate(
            ['order_number' => 'ORD-TEST-0002'],
            [
                'token_number' => 'T-002',
                'kot_number' => 'KOT-001',
                'kot_printed_at' => Carbon::now()->subMinutes(15),
                'branch_id' => $branch->id,
                'user_id' => $cashier->id,
                'pos_business_day_id' => $businessDay?->id,
                'pos_shift_id' => $shift?->id,
                'order_type_id' => $takeaway->id,
                'subtotal' => 850.00,
                'tax_amount' => 110.50,
                'grand_total' => 960.50,
                'status' => 'submitted',
                'payment_status' => 'unpaid',
                'notes' => 'Takeaway order sent to kitchen for preparation',
            ]
        );
        $order2->items()->delete();
        $order2->items()->create([
            'food_item_id' => $pizza->id,
            'quantity' => 1,
            'unit_price' => $pizza->base_price,
            'total_price' => $pizza->base_price,
            'notes' => 'Extra crispy crust',
        ]);

        // 3. Fully Settled Cash Order (Status: completed, payment: paid)
        $order3 = PosOrder::updateOrCreate(
            ['order_number' => 'ORD-TEST-0003'],
            [
                'token_number' => 'T-003',
                'kot_number' => 'KOT-002',
                'kot_printed_at' => Carbon::now()->subMinutes(45),
                'branch_id' => $branch->id,
                'user_id' => $cashier->id,
                'pos_business_day_id' => $businessDay?->id,
                'pos_shift_id' => $shift?->id,
                'order_type_id' => $dineIn->id,
                'subtotal' => 930.00,
                'tax_amount' => 120.90,
                'grand_total' => 1050.90,
                'tendered_amount' => 1100.00,
                'change_amount' => 49.10,
                'status' => 'completed',
                'payment_status' => 'paid',
                'notes' => 'Settled via Cash at register',
            ]
        );
        $order3->items()->delete();
        $order3->items()->create([
            'food_item_id' => $burger->id,
            'quantity' => 1,
            'unit_price' => $burger->base_price,
            'total_price' => $burger->base_price,
        ]);
        $order3->items()->create([
            'food_item_id' => $fries->id,
            'quantity' => 1,
            'unit_price' => $fries->base_price,
            'total_price' => $fries->base_price,
        ]);

        $order3->payments()->delete();
        $order3->payments()->create([
            'payment_type_id' => $cashPayment->id,
            'amount' => 1050.90,
            'tendered' => 1100.00,
            'change_returned' => 49.10,
            'pos_shift_id' => $shift?->id,
            'user_id' => $cashier->id,
            'transaction_reference' => 'CASH-T-003',
        ]);

        // 4. Voided / Cancelled Order with Reason
        PosOrder::updateOrCreate(
            ['order_number' => 'ORD-TEST-0004'],
            [
                'token_number' => 'T-004',
                'branch_id' => $branch->id,
                'user_id' => $cashier->id,
                'pos_business_day_id' => $businessDay?->id,
                'pos_shift_id' => $shift?->id,
                'order_type_id' => $dineIn->id,
                'subtotal' => 550.00,
                'tax_amount' => 71.50,
                'grand_total' => 621.50,
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
                'notes' => 'Voided: Customer requested cancellation before kitchen preparation',
            ]
        );
    }
}
