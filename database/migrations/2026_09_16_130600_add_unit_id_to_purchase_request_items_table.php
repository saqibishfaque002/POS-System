<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->constrained('ingredient_units')->nullOnDelete();
            $table->decimal('unit_price', 15, 2)->nullable()->change();
            $table->decimal('subtotal', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
            // Leaving unit_price and subtotal nullable for simplicity on rollback
        });
    }
};
