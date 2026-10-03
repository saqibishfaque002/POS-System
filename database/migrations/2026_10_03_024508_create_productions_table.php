<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->decimal('target_yield_qty', 10, 4);
            $table->decimal('actual_yield_qty', 10, 4)->nullable();
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
