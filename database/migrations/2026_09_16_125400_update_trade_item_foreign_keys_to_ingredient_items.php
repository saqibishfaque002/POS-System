<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'purchase_request_items',
            'purchase_order_items',
            'good_received_note_items',
            'purchase_invoice_items',
            'purchase_return_items',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table_bp) use ($table) {
                // Drop the foreign key
                $table_bp->dropForeign(['item_id']);
                // Add new foreign key pointing to ingredient_items
                $table_bp->foreign('item_id', $table . '_ingredient_id_foreign')->references('id')->on('ingredient_items')->cascadeOnDelete();
            });
        }
        
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'purchase_request_items',
            'purchase_order_items',
            'good_received_note_items',
            'purchase_invoice_items',
            'purchase_return_items',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table_bp) use ($table) {
                $table_bp->dropForeign($table . '_ingredient_id_foreign');
                $table_bp->foreign('item_id')->references('id')->on('food_items')->cascadeOnDelete();
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};
