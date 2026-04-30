<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LeaveType::truncate();
        LeaveType::insert([
            [
                'company_id' => 1,
                'name' => 'Cuti Tahunan',
                'default_quota' => 12,
                'requires_attachment' => false,
                'is_paid' => true,
            ],
            [
                'company_id' => 1,
                'name' => 'Cuti Sakit',
                'default_quota' => 10,
                'requires_attachment' => true,
                'is_paid' => true,
            ],
            [
                'company_id' => 1,
                'name' => 'Cuti Melahirkan',
                'default_quota' => 90,
                'requires_attachment' => true,
                'is_paid' => true,
            ],
            [
                'company_id' => 1,
                'name' => 'Cuti Tanpa Bayar',
                'default_quota' => 0,
                'requires_attachment' => false,
                'is_paid' => false,
            ],
        ]);
    }
}
