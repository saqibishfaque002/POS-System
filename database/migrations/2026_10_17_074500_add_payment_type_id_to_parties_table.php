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
        if (Schema::hasTable('parties') && !Schema::hasColumn('parties', 'payment_type_id')) {
            Schema::table('parties', function (Blueprint $table) {
                $table->foreignId('payment_type_id')->nullable()->after('city_id')->constrained('payment_types')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('parties') && Schema::hasColumn('parties', 'payment_type_id')) {
            Schema::table('parties', function (Blueprint $table) {
                $table->dropForeign(['payment_type_id']);
                $table->dropColumn('payment_type_id');
            });
        }
    }
};
