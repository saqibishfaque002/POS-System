<?php

namespace Database\Seeders;

use App\Models\PosBusinessDay;
use App\Models\PosShift;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SecurityTestWorkPeriodSeeder extends Seeder
{
    /**
     * Seed realistic POS business days and cashier shifts for testing idempotently.
     */
    public function run(): void
    {
        $downtown = Branch::where('name', 'Downtown Branch')->first() ?? Branch::first();
        $manager = User::where('email', 'manager.downtown@pos.local')->first() ?? User::where('role', 'manager')->first();
        $cashier = User::where('email', 'cashier.downtown@pos.local')->first() ?? User::where('role', 'cashier')->first();

        if (!$downtown || !$manager || !$cashier) return;

        // 1. Closed Business Day (Yesterday)
        $yesterday = Carbon::yesterday();
        $closedDay = PosBusinessDay::updateOrCreate(
            ['branch_id' => $downtown->id, 'business_date' => $yesterday->toDateString()],
            [
                'status' => 'closed',
                'opened_by' => $manager->id,
                'opened_at' => $yesterday->copy()->setHour(9)->setMinute(0),
                'closed_by' => $manager->id,
                'closed_at' => $yesterday->copy()->setHour(23)->setMinute(30),
                'notes' => 'Yesterday Historical Business Day - Reconciled',
            ]
        );

        // Closed Shift for Yesterday
        PosShift::updateOrCreate(
            ['pos_business_day_id' => $closedDay->id, 'shift_number' => 'SHF-TEST-YESTERDAY'],
            [
                'branch_id' => $downtown->id,
                'user_id' => $cashier->id,
                'opening_float' => 5000.00,
                'closing_cash_actual' => 18500.00,
                'closing_cash_expected' => 18500.00,
                'cash_variance' => 0.00,
                'status' => 'closed',
                'opened_at' => $yesterday->copy()->setHour(9)->setMinute(15),
                'closed_at' => $yesterday->copy()->setHour(17)->setMinute(0),
                'closed_by' => $cashier->id,
                'notes' => 'Yesterday Morning Shift - Drawer Balanced',
            ]
        );

        // 2. Active Open Business Day (Today)
        $today = Carbon::today();
        $activeDay = PosBusinessDay::updateOrCreate(
            ['branch_id' => $downtown->id, 'business_date' => $today->toDateString()],
            [
                'status' => 'open',
                'opened_by' => $manager->id,
                'opened_at' => Carbon::now()->subHours(4),
                'notes' => 'Active Business Day for Penetration and Security Testing',
            ]
        );

        // Active Open Shift for Today
        PosShift::updateOrCreate(
            ['pos_business_day_id' => $activeDay->id, 'user_id' => $cashier->id, 'status' => 'open'],
            [
                'branch_id' => $downtown->id,
                'shift_number' => 'SHF-TEST-TODAY-01',
                'opening_float' => 5000.00,
                'opened_at' => Carbon::now()->subHours(3),
                'notes' => 'Active Cashier Register Shift for Downtown Terminal Testing',
            ]
        );
    }
}
