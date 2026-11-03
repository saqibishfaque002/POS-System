<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingMasterSeeder extends Seeder
{
    /**
     * Seed master system settings idempotently.
     */
    public function run(): void
    {
        $settings = [
            // General Group
            ['group' => 'general', 'key' => 'store_name', 'value' => 'Gourmet Bistro & Cafe'],
            ['group' => 'general', 'key' => 'store_location', 'value' => 'Karachi, Pakistan'],
            ['group' => 'general', 'key' => 'currency_symbol', 'value' => 'Rs.'],
            ['group' => 'general', 'key' => 'currency_code', 'value' => 'PKR'],
            ['group' => 'general', 'key' => 'tax_rate', 'value' => '13'],
            ['group' => 'general', 'key' => 'print_mode', 'value' => 'browser'],
            ['group' => 'general', 'key' => 'auto_fetch_supplier_items', 'value' => 'true'],
            ['group' => 'general', 'key' => 'receipt_header', 'value' => 'Welcome to Gourmet Bistro - Where Quality Meets Taste'],
            ['group' => 'general', 'key' => 'receipt_footer', 'value' => 'Thank you for dining with us! For feedback call 021-35870001'],

            // POS Group
            ['group' => 'pos', 'key' => 'enable_kot', 'value' => 'true'],
            ['group' => 'pos', 'key' => 'require_shift', 'value' => 'true'],
            ['group' => 'pos', 'key' => 'allow_credit_orders', 'value' => 'true'],
            ['group' => 'pos', 'key' => 'max_discount_percent', 'value' => '30'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['group' => $setting['group'], 'value' => $setting['value']]
            );
        }
    }
}
