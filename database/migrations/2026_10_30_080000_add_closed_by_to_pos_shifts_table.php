<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_shifts') && !Schema::hasColumn('pos_shifts', 'closed_by')) {
            Schema::table('pos_shifts', function (Blueprint $table) {
                $table->foreignId('closed_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_shifts') && Schema::hasColumn('pos_shifts', 'closed_by')) {
            Schema::table('pos_shifts', function (Blueprint $table) {
                $table->dropForeign(['closed_by']);
                $table->dropColumn('closed_by');
            });
        }
    }
};
