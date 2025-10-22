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

    public function taskEta(Task $task): JsonResponse
    {
        $output = $this->service->taskEstimatedTimeOfArrivalBasic($task);

        return response()->json($output);
    }
}
