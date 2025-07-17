<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get departments and sections
        $departments = Department::all()->keyBy('name');
        $sections = Section::all()->keyBy('name');

        // Define department-section permissions
        $permissions = [
            // SuperAdmin: all sections
            'SuperAdmin' => ['dashboard', 'orders', 'clients', 'production', 'reports', 'jobs', 'settings', 'payments', 'qr_code', 'due_dates', 'job_finished'],

            // Sales Manager
            'Sales Manager' => ['dashboard', 'orders', 'clients', 'production', 'reports', 'settings', 'payments', 'due_dates'],

            // Admin
            'Admin' => ['dashboard', 'orders', 'clients', 'reports', 'settings', 'payments'],

            // Designer
            'Designer' => ['orders', 'reports', 'jobs', 'due_dates'],

            // Production Staff
            'Production Staff' => ['orders', 'production', 'reports', 'jobs', 'qr_code', 'job_finished'],
        ];

        // Insert permissions
        foreach ($permissions as $departmentName => $sectionNames) {
            $department = $departments->get($departmentName);
            if (!$department) continue;

            foreach ($sectionNames as $sectionName) {
                $section = $sections->get($sectionName);
                if (!$section) continue;

                DB::table('department_sections')->insertOrIgnore([
                    'department_id' => $department->id,
                    'section_id' => $section->section_id,
                ]);
            }
        }
    }
}
