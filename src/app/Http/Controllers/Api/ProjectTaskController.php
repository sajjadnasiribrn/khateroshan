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
    /**
     * @group Tasks
     * @authenticated
     *
     * Create a task that belongs to the given project.
     *
     * @urlParam project integer required The project id that will own the task. Example: 42
     * @bodyParam title string required Task title. Example: Prepare launch assets
     * @bodyParam description string Task description. Example: Compile creative assets for the campaign
     * @bodyParam status string Task status enum value. Example: pending
     * @bodyParam assigned_to integer User id responsible for the task. Example: 9
     * @bodyParam due_date date Due date for the task. Example: 2025-04-01
     * @bodyParam tags array A list of tag labels. Example: ["design","launch"]
     * @bodyParam estimated_time integer Estimated time to complete in minutes. Example: 120
     */
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
