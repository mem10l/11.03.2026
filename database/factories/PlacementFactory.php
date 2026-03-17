<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Internship;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Placement>
 */
class PlacementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'internship_id' => Internship::inRandomOrder()->first()?->internship_id ?? Internship::factory(),
            'student_id' => User::where('role_id', 3)->inRandomOrder()->first()?->id ?? User::factory()->student(),
            'company_id' => Company::inRandomOrder()->first()?->company_id ?? Company::factory(),
            'start_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'end_date' => fake()->optional()->dateTimeBetween('+7 months', '+12 months'),
        ];
    }
}
