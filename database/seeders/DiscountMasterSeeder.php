<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Branch;
use App\Models\OrderType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DiscountMasterSeeder extends Seeder
{
    /**
     * Seed master discounts, promotions, bank deals, and vouchers idempotently.
     */
    public function run(): void
    {
        $discounts = [
            [
                'name' => 'General Flat 10% Deal',
                'code' => 'FLAT10',
                'description' => '10% discount on order subtotal',
                'type' => 'percentage',
                'value' => 10.00,
                'scope' => 'cart',
                'min_order_amount' => 200.00,
                'max_discount_amount' => 500.00,
                'apply_tax_timing' => 'before_tax',
                'apply_addon_timing' => 'include_addons',
                'merchant_share_percentage' => 100.00,
                'partner_share_percentage' => 0.00,
                'is_bank_discount' => false,
                'requires_manager_approval' => false,
                'is_active' => true,
                'valid_from' => Carbon::now()->subMonths(1),
                'valid_to' => Carbon::now()->addYears(2),
            ],
            [
                'name' => 'HBL Credit/Debit Card 15% Privilege',
                'code' => 'HBL15',
                'description' => 'Exclusive 15% off for HBL Visa/MasterCard cardholders (BIN: 401200)',
                'type' => 'percentage',
                'value' => 15.00,
                'scope' => 'cart',
                'min_order_amount' => 500.00,
                'max_discount_amount' => 750.00,
                'apply_tax_timing' => 'before_tax',
                'apply_addon_timing' => 'include_addons',
                'merchant_share_percentage' => 50.00,
                'partner_share_percentage' => 50.00,
                'is_bank_discount' => true,
                'bank_name' => 'Habib Bank Limited (HBL)',
                'card_numbers' => "401200\n401201\n401202\n524100",
                'requires_manager_approval' => false,
                'is_active' => true,
                'valid_from' => Carbon::now()->subMonths(1),
                'valid_to' => Carbon::now()->addYears(2),
            ],
            [
                'name' => 'Flat Rs. 50 Foodie Voucher',
                'code' => 'PROMO50',
                'description' => 'Flat Rs. 50 voucher discount on minimum order Rs. 350',
                'type' => 'fixed',
                'value' => 50.00,
                'scope' => 'cart',
                'min_order_amount' => 350.00,
                'max_discount_amount' => 50.00,
                'apply_tax_timing' => 'before_tax',
                'apply_addon_timing' => 'include_addons',
                'merchant_share_percentage' => 100.00,
                'partner_share_percentage' => 0.00,
                'is_bank_discount' => false,
                'requires_manager_approval' => false,
                'is_active' => true,
                'valid_from' => Carbon::now()->subMonths(1),
                'valid_to' => Carbon::now()->addYears(2),
            ],
            [
                'name' => 'VIP Corporate Executive 20%',
                'code' => 'VIP20',
                'description' => '20% corporate discount requiring manager authorization',
                'type' => 'percentage',
                'value' => 20.00,
                'scope' => 'cart',
                'min_order_amount' => 500.00,
                'max_discount_amount' => 1500.00,
                'apply_tax_timing' => 'before_tax',
                'apply_addon_timing' => 'include_addons',
                'merchant_share_percentage' => 100.00,
                'partner_share_percentage' => 0.00,
                'is_bank_discount' => false,
                'requires_manager_approval' => true,
                'is_active' => true,
                'valid_from' => Carbon::now()->subMonths(1),
                'valid_to' => Carbon::now()->addYears(2),
            ],
        ];

        $branches = Branch::where('is_active', true)->get();
        $orderTypes = OrderType::where('is_active', true)->get();

        foreach ($discounts as $dData) {
            $discount = Discount::updateOrCreate(
                ['code' => $dData['code']],
                $dData
            );

            if ($branches->isNotEmpty()) {
                $discount->branches()->syncWithoutDetaching($branches->pluck('id')->toArray());
            }

            if ($orderTypes->isNotEmpty()) {
                $discount->orderTypes()->syncWithoutDetaching($orderTypes->pluck('id')->toArray());
            }
        }
    }
}
