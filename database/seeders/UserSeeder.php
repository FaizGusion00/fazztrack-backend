<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all()->keyBy('name');

        $users = [
            // Super Admin
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['SuperAdmin']->id,
                'production_role' => null,
            ],
            // Admin
            [
                'name' => 'System Administrator',
                'email' => 'admin@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Admin']->id,
                'production_role' => null,
            ],
            // Sales Team
            [
                'name' => 'John Sales',
                'email' => 'john.sales@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Sales']->id,
                'production_role' => null,
            ],
            [
                'name' => 'Sarah Marketing',
                'email' => 'sarah.marketing@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Sales']->id,
                'production_role' => null,
            ],
            // Designers
            [
                'name' => 'Mike Designer',
                'email' => 'mike.designer@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Designer']->id,
                'production_role' => null,
            ],
            [
                'name' => 'Lisa Creative',
                'email' => 'lisa.creative@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Designer']->id,
                'production_role' => null,
            ],
            // Production Team - Print
            [
                'name' => 'Tom Printer',
                'email' => 'tom.printer@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Production']->id,
                'production_role' => 'PRINT',
            ],
            // Production Team - Press
            [
                'name' => 'Bob Press',
                'email' => 'bob.press@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Production']->id,
                'production_role' => 'PRESS',
            ],
            // Production Team - Cut
            [
                'name' => 'Alice Cutter',
                'email' => 'alice.cutter@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Production']->id,
                'production_role' => 'CUT',
            ],
            // Production Team - Sew
            [
                'name' => 'Emma Sewer',
                'email' => 'emma.sewer@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Production']->id,
                'production_role' => 'SEW',
            ],
            // Production Team - QC
            [
                'name' => 'David QC',
                'email' => 'david.qc@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Production']->id,
                'production_role' => 'QC',
            ],
            // Production Team - Packing
            [
                'name' => 'Rachel Packer',
                'email' => 'rachel.packer@fazztrack.com',
                'password' => Hash::make('password'),
                'department_id' => $departments['Production']->id,
                'production_role' => 'PACKING',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
