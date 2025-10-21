<?php

namespace Database\Seeders;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\TaskStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = fake();

        $users = new Collection([
            User::factory()->create([
                'name' => 'Ali Rezaei',
                'email' => 'ali.rezaei@example.com',
                'role' => UserRoleEnum::ADMIN,
            ]),
            User::factory()->create([
                'name' => 'Sara Mohammadi',
                'email' => 'sara.mohammadi@example.com',
                'role' => UserRoleEnum::MANAGER,
            ]),
            User::factory()->create([
                'name' => 'Hossein Karimi',
                'email' => 'hossein.karimi@example.com',
                'role' => UserRoleEnum::MEMBER,
            ]),
        ]);

        $projectStatuses = ProjectStatus::cases();
        $projectRoles = ['owner', 'collaborator', 'reviewer'];
        $taskStatuses = TaskStatusEnum::cases();

        $projects = Project::factory()
            ->count(2)
            ->state(fn () => [
                'created_by' => $users->random()->id,
                'status' => Arr::random($projectStatuses),
                'priority' => Arr::random(ProjectPriority::cases()),
                'type' => Arr::random(ProjectType::cases()),
            ])
            ->create();

        $projects->each(function (Project $project) use ($faker, $users, $projectRoles, $taskStatuses): void {
            $project->users()->syncWithoutDetaching(
                $users->mapWithKeys(fn (User $user) => [
                    $user->id => ['role_on_project' => $projectRoles[array_rand($projectRoles)]],
                ])->all()
            );

            $taskCount = $faker->numberBetween(5, 10);
            $tasks = collect();

            for ($i = 0; $i < $taskCount; $i++) {
                $assignee = $faker->boolean(70) ? $users->random()->id : null;

                $task = Task::factory()->make([
                    'project_id' => $project->id,
                    'assigned_to' => $assignee,
                    'status' => $taskStatuses[$i % count($taskStatuses)],
                ]);

                $task->save();
                $tasks->push($task);
            }

            $tasks->each(function (Task $task) use ($faker, $tasks): void {
                $possibleDependencies = $tasks->where('id', '!=', $task->id);
                if ($possibleDependencies->isEmpty()) {
                    return;
                }

                $dependencyCount = $faker->numberBetween(0, min(2, $possibleDependencies->count()));
                if ($dependencyCount === 0) {
                    return;
                }

                $dependencies = collect(Arr::wrap($possibleDependencies->random($dependencyCount)))
                    ->pluck('id')
                    ->all();
                $task->dependencies()->syncWithoutDetaching($dependencies);
            });

            $tasks->each(function (Task $task) use ($faker, $users): void {
                $commentCount = $faker->numberBetween(0, 3);
                if ($commentCount === 0) {
                    return;
                }

                Comment::factory()
                    ->count($commentCount)
                    ->state(fn () => [
                        'task_id' => $task->id,
                        'user_id' => $users->random()->id,
                    ])
                    ->create();
            });

            Report::factory()->create([
                'project_id' => $project->id,
                'user_id' => $faker->boolean(50) ? $users->random()->id : null,
                'report_type' => 'summary',
            ]);
        });
    }
}
