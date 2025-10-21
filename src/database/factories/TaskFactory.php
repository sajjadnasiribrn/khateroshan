<?php

namespace Database\Factories;

use App\Enums\TaskStatusEnum;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $estimated = fake()->randomElement([30, 60, 90, 120, 240, null]);
        $dueDate = fake()->optional()->dateTimeBetween('now', '+2 months');

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(4),
            'assigned_to' => fake()->boolean(60) ? User::factory() : null,
            'project_id' => Project::factory(),
            'status' => fake()->randomElement(TaskStatusEnum::cases()),
            'due_date' => $dueDate?->format('Y-m-d'),
            'tags' => fake()->optional(0.5)->randomElements([
                'backend',
                'frontend',
                'bug',
                'enhancement',
                'research',
                'urgent',
            ], fake()->numberBetween(1, 3)),
            'estimated_time' => $estimated,
            'actual_time' => $estimated ? $estimated + fake()->numberBetween(-30, 120) : null,
        ];
    }
}
