<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Branch;
use App\Models\City;
use App\Models\Floor;
use App\Models\Table;
use Illuminate\Database\Seeder;

class CompanyAndBranchMasterSeeder extends Seeder
{
    /**
     * Seed master company, branches, floors, and tables idempotently.
     */
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['name' => 'Gourmet Bistro Ltd'],
            ['is_active' => true]
        );

        $karachi = City::where('name', 'Karachi')->first() ?? City::first();
        $lahore = City::where('name', 'Lahore')->first() ?? $karachi;

        $branchesData = [
            [
                'name' => 'Downtown Branch',
                'company_id' => $company->id,
                'city_id' => $karachi->id,
                'address' => 'Plot 12-C, Zamzama Commercial Lane 3, Phase 5, DHA, Karachi',
                'phone' => '+92-21-35870001',
                'delivery_charges' => 150.00,
                'service_charge_type' => 'percentage',
                'service_charge_value' => 5.00,
                'is_active' => true,
            ],
            [
                'name' => 'Uptown Branch',
                'company_id' => $company->id,
                'city_id' => $karachi->id,
                'address' => 'Shop 4-5, Block 7, Gulshan-e-Iqbal, Karachi',
                'phone' => '+92-21-34980002',
                'delivery_charges' => 200.00,
                'service_charge_type' => 'fixed',
                'service_charge_value' => 50.00,
                'is_active' => true,
            ],
            [
                'name' => 'Gulberg Branch',
                'company_id' => $company->id,
                'city_id' => $lahore->id,
                'address' => 'Main Boulevard, Gulberg III, Lahore',
                'phone' => '+92-42-35780003',
                'delivery_charges' => 180.00,
                'service_charge_type' => 'percentage',
                'service_charge_value' => 5.00,
                'is_active' => true,
            ],
        ];

        foreach ($branchesData as $bData) {
            $branch = Branch::updateOrCreate(
                ['name' => $bData['name'], 'company_id' => $company->id],
                $bData
            );

            // Seed Floors for this branch
            $floors = [
                ['name' => 'Ground Dining Hall', 'branch_id' => $branch->id, 'is_active' => true],
                ['name' => 'Family Mezzanine', 'branch_id' => $branch->id, 'is_active' => true],
                ['name' => 'Outdoor Terrace', 'branch_id' => $branch->id, 'is_active' => true],
            ];

            foreach ($floors as $floorData) {
                $floor = Floor::updateOrCreate(
                    ['name' => $floorData['name'], 'branch_id' => $branch->id],
                    $floorData
                );

                // Seed Tables for this floor
                $prefix = match ($floorData['name']) {
                    'Ground Dining Hall' => 'G',
                    'Family Mezzanine' => 'M',
                    'Outdoor Terrace' => 'T',
                    default => 'TBL',
                };

                for ($i = 1; $i <= 4; $i++) {
                    $tableName = "{$prefix}-0{$i}";
                    Table::updateOrCreate(
                        ['name' => $tableName, 'floor_id' => $floor->id],
                        [
                            'capacity' => 4,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
