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
        Schema::table('ingredient_items', function (Blueprint $table) {
            $table->foreignId('purchase_unit_id')->nullable()->constrained('ingredient_units')->nullOnDelete();
            $table->decimal('conversion_qty', 10, 4)->default(1);
            $table->foreignId('consumption_unit_id')->nullable()->constrained('ingredient_units')->nullOnDelete();
            $table->decimal('consumption_qty', 10, 4)->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredient_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_unit_id']);
            $table->dropForeign(['consumption_unit_id']);
            $table->dropColumn(['purchase_unit_id', 'conversion_qty', 'consumption_unit_id', 'consumption_qty']);
        });
    }
};
