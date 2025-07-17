<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            SectionSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            DepartmentSectionSeeder::class,
        ]);
    }
}
