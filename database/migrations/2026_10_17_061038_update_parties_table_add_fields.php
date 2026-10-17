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
        Schema::table('parties', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('party_type')->default('corporate')->after('name');
            $table->string('contact_person')->nullable()->after('party_type');
            $table->string('phone')->nullable()->after('contact_person');
            $table->string('email')->nullable()->after('phone');
            $table->string('tax_number')->nullable()->after('email');
            $table->text('address')->nullable()->after('tax_number');
            $table->foreignId('city_id')->nullable()->after('address')->constrained('cities')->nullOnDelete();
            $table->decimal('credit_limit', 15, 2)->default(0)->after('city_id');
            $table->decimal('opening_balance', 15, 2)->default(0)->after('credit_limit');
            $table->boolean('is_active')->default(true)->after('opening_balance');
            $table->text('notes')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn([
                'name',
                'party_type',
                'contact_person',
                'phone',
                'email',
                'tax_number',
                'address',
                'city_id',
                'credit_limit',
                'opening_balance',
                'is_active',
                'notes',
            ]);
        });
    }
};
