<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OfficeLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'    => \App\Models\Company::factory(),
            'name'          => fake()->company(),
            'address'       => fake()->address(),
            'latitude'      => fake()->latitude(),
            'longitude'     => fake()->longitude(),
            'radius_meters' => fake()->numberBetween(50, 500),
            'is_active'     => true,
        ];
    }
}
