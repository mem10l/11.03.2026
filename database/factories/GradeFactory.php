<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
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
            'grade' => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
            'comment' => fake()->optional(0.7)->paragraph(),
        ];
    }

    /**
     * Indicate a passing grade.
     */
    public function pass(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => fake()->randomElement(['A', 'B', 'C', 'D']),
        ]);
    }

    /**
     * Indicate a failing grade.
     */
    public function fail(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => 'F',
            'comment' => fake()->paragraph(),
        ]);
    }
}
