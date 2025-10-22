<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $service)
    {
    }

    /**
     * @group Analytics
     * @authenticated
     *
     * Get aggregated metrics for a project.
     *
     * @urlParam project integer required The project id to analyze. Example: 42
     */
    public function project(Project $project): JsonResponse
    {
        $output = $this->service->projectBasics($project);

        Report::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'report_type' => 'project_metrics',
            'data' => $output['data'] ?? [],
        ]);

        return response()->json($output);
    }

    /**
     * @group Analytics
     * @authenticated
     *
     * Get analytics information for a specific user.
     *
     * @urlParam user integer required The user id to analyze. Example: 7
     */
    public function user(User $user): JsonResponse
    {
        $output = $this->service->userBasics($user);

        Report::create([
            'project_id' => null,
            'user_id' => $user->id,
            'report_type' => 'user_metrics',
            'data' => $output['data'] ?? [],
        ]);

        return response()->json($output);
    }

    /**
     * @group Analytics
     * @authenticated
     *
     * Estimate the completion time for a task.
     *
     * @urlParam task integer required The task id to analyze. Example: 13
     */
    public function taskEta(Task $task): JsonResponse
    {
        $output = $this->service->taskEstimatedTimeOfArrivalBasic($task);

        return response()->json($output);
    }
}
