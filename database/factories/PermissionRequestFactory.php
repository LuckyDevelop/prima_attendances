<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Enums\PermissionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PermissionRequest>
 */
class PermissionRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'permission_type' => fake()->randomElement(PermissionType::cases())->value,
            'request_date'    => fake()->dateTimeBetween('today', '+30 days')->format('Y-m-d'),
            'start_time'      => '08:00:00',
            'end_time'        => '10:00:00',
            'reason'          => fake()->sentence(),
            'attachment'      => null,
            'status'          => LeaveStatus::PENDING,
            'submitted_at'    => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::PENDING,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::APPROVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::REJECTED,
        ]);
    }

    public function lateArrival(): static
    {
        return $this->state(fn (array $attributes) => [
            'permission_type' => PermissionType::LATE_ARRIVAL->value,
        ]);
    }
}
