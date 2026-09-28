<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_invoices', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('branch_id');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('shipping_amount', 15, 2)->default(0)->after('tax_amount');
        });

        Schema::table('sale_invoice_items', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('unit_price');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_invoices', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_phone', 'discount_amount', 'tax_amount', 'shipping_amount']);
        });

        Schema::table('sale_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'tax_amount']);
        });
    }
};
