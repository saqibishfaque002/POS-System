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
        // 1. Create pos_business_days table
        if (!Schema::hasTable('pos_business_days')) {
            Schema::create('pos_business_days', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->date('business_date');
                $table->foreignId('opened_by')->constrained('users');
                $table->dateTime('opened_at');
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('closed_at')->nullable();
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'status'], 'idx_pbd_branch_status');
                $table->index('business_date', 'idx_pbd_business_date');
            });
        }

        // 2. Create pos_shifts table
        if (!Schema::hasTable('pos_shifts')) {
            Schema::create('pos_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_business_day_id')->constrained('pos_business_days')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users');
                $table->string('shift_number')->unique();
                $table->decimal('opening_float', 15, 2)->default(0);
                $table->decimal('closing_cash_actual', 15, 2)->nullable();
                $table->decimal('closing_cash_expected', 15, 2)->nullable();
                $table->decimal('cash_variance', 15, 2)->nullable();
                $table->dateTime('opened_at');
                $table->dateTime('closed_at')->nullable();
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'user_id', 'status'], 'idx_ps_branch_user_status');
                $table->index('pos_business_day_id', 'idx_ps_business_day_id');
            });
        }

        // 3. Add pos_business_day_id and pos_shift_id to pos_orders
        Schema::table('pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_orders', 'pos_business_day_id')) {
                $table->foreignId('pos_business_day_id')->nullable()->after('branch_id')->constrained('pos_business_days')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_orders', 'pos_shift_id')) {
                $table->foreignId('pos_shift_id')->nullable()->after('pos_business_day_id')->constrained('pos_shifts')->nullOnDelete();
            }
            $table->index(['pos_shift_id', 'status'], 'idx_po_shift_status');
        });

        // 4. Add pos_shift_id and user_id to pos_order_payments
        Schema::table('pos_order_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_order_payments', 'pos_shift_id')) {
                $table->foreignId('pos_shift_id')->nullable()->after('pos_order_id')->constrained('pos_shifts')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_order_payments', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('pos_shift_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_order_payments', function (Blueprint $table) {
            if (Schema::hasColumn('pos_order_payments', 'pos_shift_id')) {
                $table->dropForeign(['pos_shift_id']);
                $table->dropColumn('pos_shift_id');
            }
            if (Schema::hasColumn('pos_order_payments', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropIndex('idx_po_shift_status');
            if (Schema::hasColumn('pos_orders', 'pos_shift_id')) {
                $table->dropForeign(['pos_shift_id']);
                $table->dropColumn('pos_shift_id');
            }
            if (Schema::hasColumn('pos_orders', 'pos_business_day_id')) {
                $table->dropForeign(['pos_business_day_id']);
                $table->dropColumn('pos_business_day_id');
            }
        });

        Schema::dropIfExists('pos_shifts');
        Schema::dropIfExists('pos_business_days');
    }
};
