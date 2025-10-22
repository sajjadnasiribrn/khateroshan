<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TaskController extends Controller
{
    /**
     * @group Tasks
     * @authenticated
     *
     * Update an existing task.
     *
     * @urlParam task integer required The task id. Example: 55
     * @bodyParam title string Task title. Example: Prepare launch assets
     * @bodyParam description string Task description. Example: Compile creative assets for the campaign
     * @bodyParam status string Task status enum value. Example: in_progress
     * @bodyParam assigned_to integer User id responsible for the task. Example: 9
     * @bodyParam due_date date Due date for the task. Example: 2025-04-01
     * @bodyParam tags array A list of tag labels. Example: ["design","launch"]
     * @bodyParam estimated_time integer Estimated time to complete in minutes. Example: 120
     * @bodyParam actual_time integer Actual time spent in minutes. Example: 95
     */
    public function update(Task $task, UpdateTaskRequest $request)
    {
        try {
            $task->fill($request->validated());
            $task->save();

            $task->load(['assignee', 'project']);

            return new TaskResource($task, Response::HTTP_OK);
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'unexpected error occurred.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @group Tasks
     * @authenticated
     *
     * Delete a task permanently.
     *
     * @urlParam task integer required The task id. Example: 55
     */
    public function destroy(Task $task)
    {
        try {
            $task->delete();

            return response()->noContent();
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'unexpected error occurred.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
