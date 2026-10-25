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
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'delivery_charges')) {
                $table->decimal('delivery_charges', 10, 2)->default(0)->after('phone');
            }
            if (!Schema::hasColumn('branches', 'service_charge_type')) {
                $table->enum('service_charge_type', ['percentage', 'fixed'])->default('percentage')->after('delivery_charges');
            }
            if (!Schema::hasColumn('branches', 'service_charge_value')) {
                $table->decimal('service_charge_value', 10, 2)->default(0)->after('service_charge_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('branches', 'delivery_charges')) {
                $columns[] = 'delivery_charges';
            }
            if (Schema::hasColumn('branches', 'service_charge_type')) {
                $columns[] = 'service_charge_type';
            }
            if (Schema::hasColumn('branches', 'service_charge_value')) {
                $columns[] = 'service_charge_value';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
