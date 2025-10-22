<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreTaskCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Task;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TaskCommentController extends Controller
{
    /**
     * @group Task Comments
     * @authenticated
     *
     * Add a comment to the given task.
     *
     * @urlParam task integer required The task id to comment on. Example: 55
     * @bodyParam comment string required The comment body. Example: Please review the updated copy.
     * @bodyParam rating integer Rating between 1 and 5. Example: 4
     */
    public function store(Task $task, StoreTaskCommentRequest $request)
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validated();

        try {
            $comment = $task->comments()->create([
                'user_id' => $user->id,
                'comment' => $data['comment'],
                'rating' => $data['rating'] ?? null,
            ]);

            $comment->load(['user']);

            return new CommentResource($comment, Response::HTTP_CREATED);
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'An unexpected error occurred.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
