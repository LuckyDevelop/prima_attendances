<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    public function definition(): array
    {
        $totalQuota = fake()->numberBetween(5, 20);
        $used       = fake()->numberBetween(0, $totalQuota);

        return [
            'user_id'       => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'year'          => now()->year,
            'total_quota'   => $totalQuota,
            'used'          => $used,
            'remaining'     => $totalQuota - $used,
        ];
    }

    public function withQuota(int $quota, int $used = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'total_quota' => $quota,
            'used'        => $used,
            'remaining'   => $quota - $used,
        ]);
    }
}
