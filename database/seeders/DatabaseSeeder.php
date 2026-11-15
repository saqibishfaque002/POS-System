<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application database cleanly and idempotently.
     */
    public function run(): void
    {
        // 1. Seed complete master data (Reference, configuration, catalog, recipes)
        $this->call(MasterDataSeeder::class);

        // 2. Seed test actors and realistic scenarios (Only in local or testing environments)
        if (app()->environment('local', 'testing')) {
            $this->call(SecurityTestDataSeeder::class);
        }
    }
}
