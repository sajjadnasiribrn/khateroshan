<?php

namespace Tests\Feature;

use App\Enums\TaskStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_task_for_project(): void
    {
        $manager = User::factory()->create([
            'role' => UserRoleEnum::MANAGER,
        ]);

        Passport::actingAs($manager);

        $project = Project::factory()->create();
        $assignee = User::factory()->create();

        $payload = [
            'title' => 'Set up onboarding flow',
            'description' => 'Implement onboarding checklist for new members',
            'status' => TaskStatusEnum::IN_PROGRESS->value,
            'assigned_to' => $assignee->id,
            'due_date' => now()->addWeek()->toDateString(),
            'tags' => ['onboarding', 'priority'],
            'estimated_time' => 180,
        ];

        $response = $this->postJson("/api/projects/{$project->id}/tasks", $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.project_id', $project->id)
            ->assertJsonPath('data.assigned_to', $assignee->id)
            ->assertJsonPath('data.status', TaskStatusEnum::IN_PROGRESS->value);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Set up onboarding flow',
            'status' => TaskStatusEnum::IN_PROGRESS->value,
        ]);
    }

    public function test_it_updates_task_fields(): void
    {
        $manager = User::factory()->create([
            'role' => UserRoleEnum::MANAGER,
        ]);

        Passport::actingAs($manager);

        $project = Project::factory()->create();
        $task = Task::factory()->for($project)->create([
            'status' => TaskStatusEnum::TODO,
        ]);
        $assignee = User::factory()->create();

        $payload = [
            'status' => TaskStatusEnum::DONE->value,
            'assigned_to' => $assignee->id,
            'estimated_time' => 240,
            'actual_time' => 210,
        ];

        $response = $this->patchJson("/api/tasks/{$task->id}", $payload);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatusEnum::DONE->value)
            ->assertJsonPath('data.assigned_to', $assignee->id)
            ->assertJsonPath('data.actual_time', 210);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatusEnum::DONE->value,
            'assigned_to' => $assignee->id,
            'actual_time' => 210,
        ]);
    }

    public function test_it_requires_fields_for_task_update(): void
    {
        $manager = User::factory()->create([
            'role' => UserRoleEnum::MANAGER,
        ]);

        Passport::actingAs($manager);

        $task = Task::factory()->create();

        $response = $this->patchJson("/api/tasks/{$task->id}", []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data']);
    }

    public function test_it_deletes_task(): void
    {
        $manager = User::factory()->create([
            'role' => UserRoleEnum::MANAGER,
        ]);

        Passport::actingAs($manager);

        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_it_adds_comment_to_task(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $task = Task::factory()->create();

        $response = $this->postJson("/api/tasks/{$task->id}/comments", [
            'comment' => 'Great progress on the task',
            'rating' => 5,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.comment', 'Great progress on the task')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'comment' => 'Great progress on the task',
            'rating' => 5,
        ]);
    }
}
