<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
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
            'status_id' => ApplicationStatus::inRandomOrder()->first()?->status_id ?? 1,
            'motivation_letter' => fake()->optional(0.8)->paragraphs(3, true),
            'submitted_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the application is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => 1,
        ]);
    }

    /**
     * Indicate that the application is submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => 2,
        ]);
    }

    /**
     * Indicate that the application is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => 4,
        ]);
    }

    /**
     * Indicate that the application is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => 5,
        ]);
    }
}
