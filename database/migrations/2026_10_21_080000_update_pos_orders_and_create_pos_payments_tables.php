<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update pos_orders table with missing fields
        Schema::table('pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_orders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('party_id')->constrained('customers')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_orders', 'token_number')) {
                $table->string('token_number')->nullable()->after('order_number');
            }
            if (!Schema::hasColumn('pos_orders', 'kot_number')) {
                $table->string('kot_number')->nullable()->after('token_number');
            }
            if (!Schema::hasColumn('pos_orders', 'kot_printed_at')) {
                $table->timestamp('kot_printed_at')->nullable()->after('kot_number');
            }
            if (!Schema::hasColumn('pos_orders', 'discount_id')) {
                $table->foreignId('discount_id')->nullable()->after('discount_value')->constrained('discounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_orders', 'service_charges')) {
                $table->decimal('service_charges', 15, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('pos_orders', 'delivery_charges')) {
                $table->decimal('delivery_charges', 15, 2)->default(0)->after('service_charges');
            }
            if (!Schema::hasColumn('pos_orders', 'tendered_amount')) {
                $table->decimal('tendered_amount', 15, 2)->default(0)->after('grand_total');
            }
            if (!Schema::hasColumn('pos_orders', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->default(0)->after('tendered_amount');
            }
            if (!Schema::hasColumn('pos_orders', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('pos_orders', 'rider_name')) {
                $table->string('rider_name')->nullable()->after('delivery_address');
            }
            if (!Schema::hasColumn('pos_orders', 'card_bin')) {
                $table->string('card_bin', 10)->nullable()->after('rider_name');
            }
        });

        // 2. Fix pos_order_item_properties table
        Schema::table('pos_order_item_properties', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_order_item_properties', 'pos_order_item_id')) {
                $table->foreignId('pos_order_item_id')->after('id')->constrained('pos_order_items')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('pos_order_item_properties', 'property_item_id')) {
                $table->foreignId('property_item_id')->nullable()->after('pos_order_item_id')->constrained('property_items')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_order_item_properties', 'property_name')) {
                $table->string('property_name')->after('property_item_id');
            }
            if (!Schema::hasColumn('pos_order_item_properties', 'extra_price')) {
                $table->decimal('extra_price', 15, 2)->default(0)->after('property_name');
            }
            if (!Schema::hasColumn('pos_order_item_properties', 'quantity')) {
                $table->integer('quantity')->default(1)->after('extra_price');
            }
        });

        // 3. Create pos_order_payments table for split & detailed payments
        if (!Schema::hasTable('pos_order_payments')) {
            Schema::create('pos_order_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
                $table->foreignId('payment_type_id')->constrained('payment_types');
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('tendered', 15, 2)->nullable();
                $table->decimal('change_returned', 15, 2)->default(0);
                $table->string('transaction_reference')->nullable();
                $table->string('card_last_four', 4)->nullable();
                $table->string('card_bank')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_order_payments');

        Schema::table('pos_order_item_properties', function (Blueprint $table) {
            if (Schema::hasColumn('pos_order_item_properties', 'pos_order_item_id')) {
                $table->dropForeign(['pos_order_item_id']);
                $table->dropColumn('pos_order_item_id');
            }
            if (Schema::hasColumn('pos_order_item_properties', 'property_item_id')) {
                $table->dropForeign(['property_item_id']);
                $table->dropColumn('property_item_id');
            }
            $table->dropColumn(array_filter(['property_name', 'extra_price', 'quantity'], fn($c) => Schema::hasColumn('pos_order_item_properties', $c)));
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            if (Schema::hasColumn('pos_orders', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('pos_orders', 'discount_id')) {
                $table->dropForeign(['discount_id']);
                $table->dropColumn('discount_id');
            }
            $table->dropColumn(array_filter([
                'token_number', 'kot_number', 'kot_printed_at',
                'service_charges', 'delivery_charges', 'tendered_amount', 'change_amount',
                'delivery_address', 'rider_name', 'card_bin'
            ], fn($c) => Schema::hasColumn('pos_orders', $c)));
        });
    }
};
