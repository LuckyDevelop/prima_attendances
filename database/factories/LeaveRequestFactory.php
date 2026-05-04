<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 day', '+30 days');
        $endDate   = fake()->dateTimeBetween($startDate, '+60 days');

        return [
            'user_id'       => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date'    => $startDate->format('Y-m-d'),
            'end_date'      => $endDate->format('Y-m-d'),
            'total_days'    => fake()->numberBetween(1, 14),
            'reason'        => fake()->sentence(),
            'attachment'    => null,
            'status'        => LeaveStatus::PENDING,
            'submitted_at'  => now(),
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
}
