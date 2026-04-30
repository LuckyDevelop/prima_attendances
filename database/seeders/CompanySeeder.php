<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::truncate();
        Company::insert([
            'name' => 'PT Prima Synergy Petroleum Indonesia',
            'logo' => null,
            'timezone' => 'Asia/Jakarta',
            'late_tolerance_min' => 5,
        ]);
    }
}
