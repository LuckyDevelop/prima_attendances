<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'               => fake()->company(),
            'logo'               => null,
            'timezone'           => 'Asia/Jakarta',
            'late_tolerance_min' => 5,
        ];
    }
}
