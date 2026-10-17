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
        if (!Schema::hasTable('order_type_party')) {
            Schema::create('order_type_party', function (Blueprint $table) {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('order_type_id')->constrained('order_types')->cascadeOnDelete();
                $table->unique(['party_id', 'order_type_id']);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_type_party');
    }
};
