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
        // 1. Rename existing pivot table
        Schema::rename('variations', 'food_item_variations');

        // 2. Create the new global variations table
        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Add variation_id to food_item_variations
        Schema::table('food_item_variations', function (Blueprint $table) {
            $table->unsignedBigInteger('variation_id')->nullable()->after('food_item_id');
        });

        // 4. Data Migration
        $legacyVariations = \Illuminate\Support\Facades\DB::table('food_item_variations')->get();
        foreach ($legacyVariations as $legacy) {
            $globalVar = \Illuminate\Support\Facades\DB::table('variations')->where('name', $legacy->name)->first();
            if (!$globalVar) {
                $globalId = \Illuminate\Support\Facades\DB::table('variations')->insertGetId([
                    'name' => $legacy->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $globalId = $globalVar->id;
            }

            \Illuminate\Support\Facades\DB::table('food_item_variations')
                ->where('id', $legacy->id)
                ->update(['variation_id' => $globalId]);
        }

        // 5. Add FK and drop old name
        Schema::table('food_item_variations', function (Blueprint $table) {
            $table->foreign('variation_id')->references('id')->on('variations')->cascadeOnDelete();
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('food_item_variations', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->dropForeign(['variation_id']);
            $table->dropColumn('variation_id');
        });

        Schema::dropIfExists('variations');
        Schema::rename('food_item_variations', 'variations');
    }
};
