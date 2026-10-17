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
        if (!Schema::hasTable('party_payment_type')) {
            Schema::create('party_payment_type', function (Blueprint $table) {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('payment_type_id')->constrained('payment_types')->cascadeOnDelete();
                $table->unique(['party_id', 'payment_type_id']);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_payment_type');
    }
};
