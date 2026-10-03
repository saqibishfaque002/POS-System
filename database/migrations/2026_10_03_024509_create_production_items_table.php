<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->onDelete('cascade');
            $table->foreignId('ingredient_item_id')->constrained('ingredient_items')->onDelete('cascade');
            $table->decimal('expected_qty', 10, 4);
            $table->decimal('actual_qty', 10, 4)->nullable();
            $table->foreignId('unit_id')->constrained('ingredient_units')->onDelete('cascade');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_items');
    }
};
