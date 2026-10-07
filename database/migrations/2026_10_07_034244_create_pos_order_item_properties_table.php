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
        Schema::create('pos_order_item_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_item_id')->constrained('pos_order_items')->onDelete('cascade');
            $table->foreignId('property_item_id')->constrained('property_items');
            $table->decimal('extra_price', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_order_item_properties');
    }
};
