<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\OrderType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->boolean('is_bank_discount')->default(false)->after('scope');
            $table->string('bank_name')->nullable()->after('is_bank_discount');
            $table->string('card_type')->nullable()->after('bank_name');
            $table->text('card_numbers')->nullable()->after('card_type');
            
            // Sharing with partner/bank
            $table->foreignId('party_id')->nullable()->after('card_numbers')->constrained('parties')->nullOnDelete();
            $table->decimal('merchant_share_percentage', 5, 2)->default(100.00)->after('party_id');
            $table->decimal('partner_share_percentage', 5, 2)->default(0.00)->after('merchant_share_percentage');

            // Tax and addon timing
            $table->string('apply_tax_timing')->default('before_tax')->after('partner_share_percentage'); // 'before_tax' or 'after_tax'
            $table->string('apply_addon_timing')->default('with_addon')->after('apply_tax_timing'); // 'with_addon' or 'without_addon'

            // Single datetime fields for From and To
            $table->dateTime('valid_from')->nullable()->after('apply_addon_timing');
            $table->dateTime('valid_to')->nullable()->after('valid_from');
        });

        // Ensure default Order Types exist if table is empty
        if (OrderType::count() === 0) {
            $defaultTypes = [
                ['name' => 'Dine-in', 'code' => 'dine_in', 'is_active' => true],
                ['name' => 'Takeaway', 'code' => 'takeaway', 'is_active' => true],
                ['name' => 'Delivery', 'code' => 'delivery', 'is_active' => true],
            ];
            foreach ($defaultTypes as $type) {
                OrderType::firstOrCreate(['name' => $type['name']], $type);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropForeign(['party_id']);
            $table->dropColumn([
                'is_bank_discount',
                'bank_name',
                'card_type',
                'card_numbers',
                'party_id',
                'merchant_share_percentage',
                'partner_share_percentage',
                'apply_tax_timing',
                'apply_addon_timing',
                'valid_from',
                'valid_to',
            ]);
        });
    }
};
