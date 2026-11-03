<?php

namespace Database\Seeders;

use App\Models\OrderType;
use App\Models\PaymentType;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class OrderTypeAndPaymentTypeMasterSeeder extends Seeder
{
    /**
     * Seed master order types, payment types, and branch-payment mappings idempotently.
     */
    public function run(): void
    {
        // 1. Master Order Types
        $orderTypes = [
            ['code' => 'dine_in', 'name' => 'Dine-in', 'service_charge_percent' => 0.00, 'is_active' => true],
            ['code' => 'takeaway', 'name' => 'Takeaway', 'service_charge_percent' => 0.00, 'is_active' => true],
            ['code' => 'delivery', 'name' => 'Delivery', 'service_charge_percent' => 0.00, 'is_active' => true],
            ['code' => 'drive_thru', 'name' => 'Drive-Thru', 'service_charge_percent' => 0.00, 'is_active' => true],
        ];

        foreach ($orderTypes as $ot) {
            OrderType::firstOrCreate(
                ['code' => $ot['code']],
                $ot
            );
        }

        // 2. Master Payment Types
        $paymentTypes = [
            ['name' => 'Cash', 'is_active' => true],
            ['name' => 'Credit Card', 'is_active' => true],
            ['name' => 'Debit Card', 'is_active' => true],
            ['name' => 'Bank Transfer', 'is_active' => true],
            ['name' => 'Mobile Wallet (JazzCash / EasyPaisa)', 'is_active' => true],
            ['name' => 'Complimentary / FOC', 'is_active' => true],
        ];

        $createdPaymentTypes = [];
        foreach ($paymentTypes as $pt) {
            $createdPaymentTypes[] = PaymentType::firstOrCreate(
                ['name' => $pt['name']],
                $pt
            );
        }

        // 3. Attach Payment Types to All Active Branches with Tax Percentages
        $branches = Branch::where('is_active', true)->get();
        foreach ($branches as $branch) {
            foreach ($createdPaymentTypes as $ptModel) {
                $taxPercentage = ($ptModel->name === 'Complimentary / FOC') ? 0.00 : 13.00;
                $branch->paymentTypes()->syncWithoutDetaching([
                    $ptModel->id => ['tax_percentage' => $taxPercentage]
                ]);
            }
        }
    }
}
