<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecurityTestUserSeeder extends Seeder
{
    /**
     * Seed security testing user accounts covering all roles, branch assignments, and permission levels.
     */
    public function run(): void
    {
        $downtown = Branch::where('name', 'Downtown Branch')->first() ?? Branch::first();
        $uptown = Branch::where('name', 'Uptown Branch')->first() ?? $downtown;

        $testPassword = Hash::make('Password@123!');

        $users = [
            [
                'email' => 'admin@pos.local',
                'name' => 'System Super Admin',
                'role' => 'admin',
                'phone' => '0300-9990001',
                'is_active' => true,
                'branches' => [$downtown?->id, $uptown?->id],
            ],
            [
                'email' => 'manager.downtown@pos.local',
                'name' => 'Downtown Branch Manager',
                'role' => 'manager',
                'phone' => '0300-9990002',
                'is_active' => true,
                'branches' => [$downtown?->id],
            ],
            [
                'email' => 'multimanager@pos.local',
                'name' => 'Regional Area Multi-Branch Manager',
                'role' => 'multi branch manager',
                'phone' => '0300-9990003',
                'is_active' => true,
                'branches' => array_filter([$downtown?->id, $uptown?->id]),
            ],
            [
                'email' => 'cashier.downtown@pos.local',
                'name' => 'Downtown Register Cashier 1',
                'role' => 'cashier',
                'phone' => '0300-9990004',
                'is_active' => true,
                'branches' => [$downtown?->id],
            ],
            [
                'email' => 'cashier.uptown@pos.local',
                'name' => 'Uptown Register Cashier 2',
                'role' => 'cashier',
                'phone' => '0300-9990005',
                'is_active' => true,
                'branches' => [$uptown?->id],
            ],
            [
                'email' => 'staff.user@pos.local',
                'name' => 'Low Privilege Floor Staff',
                'role' => 'user',
                'phone' => '0300-9990006',
                'is_active' => true,
                'branches' => [$downtown?->id],
            ],
            [
                'email' => 'inactive.cashier@pos.local',
                'name' => 'Deactivated Former Cashier',
                'role' => 'cashier',
                'phone' => '0300-9990007',
                'is_active' => false,
                'branches' => [$downtown?->id],
            ],
        ];

        foreach ($users as $uData) {
            $branchIds = array_filter($uData['branches'] ?? []);
            unset($uData['branches']);

            $user = User::updateOrCreate(
                ['email' => $uData['email']],
                array_merge($uData, ['password' => $testPassword])
            );

            if (!empty($branchIds)) {
                $user->branches()->syncWithoutDetaching($branchIds);
            }
        }
    }
}
