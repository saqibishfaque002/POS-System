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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->enum('scope', ['cart', 'category', 'item'])->default('cart');
            $table->decimal('min_order_amount', 15, 2)->nullable();
            $table->decimal('max_discount_amount', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('days_of_week')->nullable();
            $table->boolean('requires_manager_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('discount_branch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->unique(['discount_id', 'branch_id']);
        });

        Schema::create('discount_order_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('order_type_id')->constrained('order_types')->cascadeOnDelete();
            $table->unique(['discount_id', 'order_type_id']);
        });

        Schema::create('discount_food_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('food_group_id')->constrained('food_groups')->cascadeOnDelete();
            $table->unique(['discount_id', 'food_group_id']);
        });

        Schema::create('discount_food_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('food_item_id')->constrained('food_items')->cascadeOnDelete();
            $table->unique(['discount_id', 'food_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_food_item');
        Schema::dropIfExists('discount_food_group');
        Schema::dropIfExists('discount_order_type');
        Schema::dropIfExists('discount_branch');
        Schema::dropIfExists('discounts');
    }
};
