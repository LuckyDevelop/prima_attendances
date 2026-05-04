<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'              => \App\Models\User::factory(),
            'office_location_id'   => \App\Models\OfficeLocation::factory(),
            'work_date'            => today(),
            'check_in_time'        => now()->subHours(8),
            'check_out_time'       => now(),
            'check_in_lat'         => fake()->latitude(),
            'check_in_lng'         => fake()->longitude(),
            'check_out_lat'        => fake()->latitude(),
            'check_out_lng'        => fake()->longitude(),
            'check_in_selfie'      => null,
            'check_out_selfie'     => null,
            'is_mock_location'     => false,
            'face_verified'        => true,
            'status'               => AttendanceStatus::PRESENT,
            'work_duration_min'    => 480,
            'notes'                => null,
        ];
    }
}
