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
        Schema::table('property_groups', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
        Schema::table('property_items', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
        Schema::table('variations', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_groups', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('property_items', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('variations', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
