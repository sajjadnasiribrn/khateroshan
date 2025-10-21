<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => fake()->boolean(70) ? User::factory() : null,
            'report_type' => fake()->randomElement(['summary', 'progress', 'retrospective', 'risk']),
            'data' => [
                'overview' => fake()->paragraph(),
                'metrics' => [
                    'completed_tasks' => fake()->numberBetween(0, 20),
                    'open_issues' => fake()->numberBetween(0, 10),
                ],
                'next_steps' => fake()->sentences(2),
            ],
        ];
    }
}

