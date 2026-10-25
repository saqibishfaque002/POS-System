<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'submitted' to status enum in pos_orders
        DB::statement("ALTER TABLE `pos_orders` MODIFY COLUMN `status` ENUM('pending', 'submitted', 'preparing', 'ready', 'delivered', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `pos_orders` MODIFY COLUMN `status` ENUM('pending', 'preparing', 'ready', 'delivered', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
