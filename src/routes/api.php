<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectTaskController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskController;
use App\Models\Project;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:api')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->group(function (): void {
    Route::get('/projects', [ProjectController::class, 'index']);

    Route::post('/projects', [ProjectController::class, 'store'])
        ->middleware(['can:create,'.Project::class, 'throttle:30,1']);

    Route::patch('/projects/{project}', [ProjectController::class, 'update'])
        ->middleware(['can:update,project', 'throttle:30,1']);

    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
        ->middleware(['can:delete,project', 'throttle:30,1']);

    Route::post('/projects/{project}/tasks', [ProjectTaskController::class, 'store'])
        ->middleware(['can:create,project', 'throttle:30,1']);

    Route::patch('/tasks/{task}', [TaskController::class, 'update'])
        ->middleware(['can:update,task', 'throttle:30,1']);

    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
        ->middleware(['can:delete,task', 'throttle:30,1']);

    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);
});
