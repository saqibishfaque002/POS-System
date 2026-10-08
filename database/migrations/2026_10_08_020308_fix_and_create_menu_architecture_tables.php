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
        // 1. Drop the incorrect table if it exists
        Schema::dropIfExists('branch_menu_and_item_pivot_tables');
        Schema::dropIfExists('food_item_menu');
        Schema::dropIfExists('branch_food_item');
        Schema::dropIfExists('branch_menu');

        // 2. Fix the menus table
        if (!Schema::hasColumn('menus', 'name')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->string('name')->after('id');
                $table->text('description')->nullable()->after('name');
                $table->boolean('is_active')->default(true)->after('description');
            });
        }
        if (!Schema::hasColumn('menus', 'tax_percentage')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->decimal('tax_percentage', 5, 2)->default(0)->after('is_active');
                $table->decimal('discount_percentage', 5, 2)->default(0)->after('tax_percentage');
            });
        }

        // 3. Create branch_menu table
        Schema::create('branch_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['branch_id', 'menu_id']);
        });

        // 4. Create branch_food_item table
        Schema::create('branch_food_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('custom_price', 10, 2)->nullable()->comment('Branch specific base price override');
            $table->boolean('is_active')->default(true)->comment('Out of stock toggle for this branch');
            $table->timestamps();
            
            $table->unique(['branch_id', 'food_item_id']);
        });

        // 5. Create food_item_menu table
        Schema::create('food_item_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('food_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable()->comment('Menu specific price override');
            $table->timestamps();
            
            $table->unique(['menu_id', 'food_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_item_menu');
        Schema::dropIfExists('branch_food_item');
        Schema::dropIfExists('branch_menu');

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['tax_percentage', 'discount_percentage']);
        });
    }
};
