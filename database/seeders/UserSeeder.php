<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();
        User::insert([
            'company_id' => 1,
            'full_name' => 'Superadmin',
            'email' => 'superadmin@gmail.com',
            'department_id' => null,
            'office_location_id' => null,
            'employee_id' => 'EMP001',
            'phone' => null,
            'photo' => null,
            'face_embedding' => null,
            'role' => 'admin',
            'status' => 'active',
            'password' => bcrypt('prima1682022'),
        ]);
    }
}
