<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::truncate();
        Department::insert([
            [
                'company_id' => 1,
                'name' => 'IT',
            ],
            [
                'company_id' => 1,
                'name' => 'HR',
            ],
            [
                'company_id' => 1,
                'name' => 'Finance',
            ],
            [
                'company_id' => 1,
                'name' => 'Operational',
            ],
            [
                'company_id' => 1,
                'name' => 'General Manager',
            ],
            [
                'company_id' => 1,
                'name' => 'Marketing',
            ],
            [
                'company_id' => 1,
                'name' => 'Admin',
            ]
        ]);
    }
}
