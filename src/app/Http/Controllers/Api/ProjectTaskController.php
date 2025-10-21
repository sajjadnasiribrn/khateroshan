<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProjectTaskController extends Controller
{
    public function store(Project $project, StoreTaskRequest $request)
    {
        try {
            $task = $project->tasks()->create($request->validated());

            $task->load(['assignee', 'project']);

            return new TaskResource($task, Response::HTTP_CREATED);
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'unexpected error occurred.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
