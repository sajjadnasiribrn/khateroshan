<?php

namespace Tests\Feature;

use App\Enums\ProjectPriorityEnum;
use App\Enums\ProjectStatusEnum;
use App\Enums\ProjectTypeEnum;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_projects_with_filters_and_search(): void
    {
        $user = User::factory()->create();

        $matching = Project::factory()
            ->for($user, 'creator')
            ->create([
                'title' => 'Alpha Launch',
                'description' => 'Preparing the alpha release',
                'status' => ProjectStatusEnum::ACTIVE,
                'priority' => ProjectPriorityEnum::HIGH,
                'type' => ProjectTypeEnum::SOFTWARE,
                'recurring' => true,
                'start_date' => '2024-01-10',
                'end_date' => '2024-06-15',
            ]);

        Project::factory()
            ->count(2)
            ->create([
                'status' => ProjectStatusEnum::DRAFT,
                'priority' => ProjectPriorityEnum::LOW,
                'type' => ProjectTypeEnum::MARKETING,
                'title' => 'Beta Planning',
                'description' => 'Different project',
            ]);

        $response = $this->getJson('/api/projects?status=active&priority=high&type=software&recurring=1&created_by='.$user->id.'&q=Alpha&start_date=2024-01-01&end_date=2024-12-31');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.title', 'Alpha Launch')
            ->assertJsonPath('data.0.recurring', true);
    }

    public function test_it_creates_project(): void
    {
        $user = User::factory()->create();

        $payload = [
            'title' => 'New Integration',
            'description' => 'Integrating new payment gateway',
            'status' => ProjectStatusEnum::ACTIVE->value,
            'priority' => ProjectPriorityEnum::CRITICAL->value,
            'type' => ProjectTypeEnum::SOFTWARE->value,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'recurring' => true,
            'created_by' => $user->id,
            'budget' => 12500.50,
            'attachments' => [
                ['name' => 'Requirements.pdf', 'url' => 'https://example.com/requirements.pdf'],
            ],
        ];

        $response = $this->postJson('/api/projects', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'New Integration')
            ->assertJsonPath('data.priority', ProjectPriorityEnum::CRITICAL->value)
            ->assertJsonPath('data.recurring', true)
            ->assertJsonPath('data.attachments.0.name', 'Requirements.pdf');

        $this->assertDatabaseHas('projects', [
            'title' => 'New Integration',
            'created_by' => $user->id,
            'status' => ProjectStatusEnum::ACTIVE->value,
            'priority' => ProjectPriorityEnum::CRITICAL->value,
        ]);
    }

    public function test_it_validates_project_creation(): void
    {
        $response = $this->postJson('/api/projects', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'created_by']);
    }
}
