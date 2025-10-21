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
