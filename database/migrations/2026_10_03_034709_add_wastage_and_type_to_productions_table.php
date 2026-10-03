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
        Schema::table('productions', function (Blueprint $table) {
            $table->string('production_type')->default('raw_material')->after('recipe_id');
            $table->decimal('wastage_qty', 15, 2)->default(0)->after('actual_yield_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn(['production_type', 'wastage_qty']);
        });
    }
};
