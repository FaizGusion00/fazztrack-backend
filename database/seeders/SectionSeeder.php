<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // The sections are already created in the migration
        // This seeder can be used to add additional sections if needed
        $additionalSections = [
            'dashboard',
            'production',
            'reports',
            'settings',
        ];

        foreach ($additionalSections as $sectionName) {
            DB::table('sections')->insertOrIgnore([
                'name' => $sectionName
            ]);
        }
    }
}
