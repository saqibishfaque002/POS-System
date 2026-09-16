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
            $table->decimal('reorder_level', 10, 4)->default(0)->after('consumption_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredient_items', function (Blueprint $table) {
            $table->dropColumn('reorder_level');
        });
    }
};
