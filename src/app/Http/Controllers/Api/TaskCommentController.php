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
