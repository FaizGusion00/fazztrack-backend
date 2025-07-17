<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Admin',
                'description' => 'Administrative department with full system access'
            ],
            [
                'name' => 'SuperAdmin',
                'description' => 'Super administrative department with complete system control'
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales department responsible for client orders and relationships'
            ],
            [
                'name' => 'Designer',
                'description' => 'Design department responsible for creating and finalizing designs'
            ],
            [
                'name' => 'Production',
                'description' => 'Production department responsible for manufacturing and quality control'
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['name' => $department['name']],
                $department
            );
        }
    }
}
