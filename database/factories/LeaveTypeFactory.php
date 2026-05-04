<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'          => Company::factory(),
            'name'                => fake()->randomElement(['Cuti Tahunan', 'Cuti Sakit', 'Cuti Melahirkan', 'Cuti Tanpa Bayar']),
            'default_quota'       => fake()->numberBetween(0, 30),
            'requires_attachment' => false,
            'is_paid'             => true,
        ];
    }

    public function requiresAttachment(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_attachment' => true,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid'       => false,
            'default_quota' => 0,
        ]);
    }
}
