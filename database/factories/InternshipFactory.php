<?php

namespace Database\Factories;

use App\Models\GradingType;
use App\Models\Internship;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Internship>
 */
class InternshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle() . ' Internship',
            'start_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'end_date' => fake()->dateTimeBetween('+7 months', '+12 months'),
            'class_id' => SchoolClass::factory(),
            'supervisor_id' => User::factory()->supervisor(),
            'grading_type_id' => 1,
        ];
    }
}
