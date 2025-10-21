<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 months', '+1 week');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(10, 60).' days');

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(3),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'priority' => fake()->randomElement(ProjectPriority::cases()),
            'type' => fake()->randomElement(ProjectType::cases()),
            'recurring' => fake()->boolean(15),
            'created_by' => User::factory(),
            'budget' => fake()->optional()->randomFloat(2, 5000, 150000),
            'attachments' => fake()->optional(0.4)->randomElements([
                ['name' => 'Brief.pdf', 'url' => fake()->url()],
                ['name' => 'Roadmap.xlsx', 'url' => fake()->url()],
                ['name' => 'Wireframe.png', 'url' => fake()->url()],
            ], fake()->numberBetween(1, 2)),
        ];
    }
}
