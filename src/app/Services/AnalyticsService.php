<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * @return array{
     *     meta: array{project_id: int, cached: bool, generated_at: string},
     *     data: array{
     *         progress_percent: float,
     *         counts: array{total: int, done: int, pending: int, overdue: int},
     *     },
     *     summary: string
     * }
     */
    public function projectBasics(Project $project): array
    {
        $cacheKey = "analytics:project:{$project->id}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($project) {
            $today = Carbon::today();

            $taskQuery = Task::query()->where('project_id', $project->id);

            $total = (clone $taskQuery)->count();
            $done = (clone $taskQuery)
                ->where('status', TaskStatusEnum::DONE->value)
                ->count();
            $overdue = (clone $taskQuery)
                ->where('status', '!=', TaskStatusEnum::DONE->value)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today)
                ->count();
            $pending = max($total - $done, 0);
            $progress = $total > 0 ? round(($done / $total) * 100, 1) : 0.0;

            $summary = 'Progress '.$progress.'%, '.$overdue.' tasks overdue.';

            return [
                'meta' => [
                    'project_id' => $project->id,
                    'cached' => false,
                    'generated_at' => Carbon::now()->toIso8601String(),
                ],
                'data' => [
                    'progress_percent' => $progress,
                    'counts' => [
                        'total' => $total,
                        'done' => $done,
                        'pending' => $pending,
                        'overdue' => $overdue,
                    ],
                ],
                'summary' => $summary,
            ];
        });

        return $payload;
    }

    /**
     * @return array{
     *     meta: array{user_id: int, cached: bool, generated_at: string},
     *     data: array{
     *         completed_count: int,
     *         avg_actual_time: float|null,
     *         avg_estimated_time: float|null,
     *         avg_delay_minutes: float|null,
     *     },
     *     summary: string
     * }
     */
    public function userBasics(User $user): array
    {
        $cacheKey = "analytics:user:{$user->id}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            $taskQuery = Task::query()
                ->where('assigned_to', $user->id)
                ->where('status', TaskStatusEnum::DONE->value);

            $completedCount = (clone $taskQuery)->count();
            $avgActual = (clone $taskQuery)
                ->whereNotNull('actual_time')
                ->avg('actual_time');
            $avgEstimated = (clone $taskQuery)
                ->whereNotNull('estimated_time')
                ->avg('estimated_time');
            $avgDelay = (clone $taskQuery)
                ->whereNotNull('actual_time')
                ->whereNotNull('estimated_time')
                ->select(DB::raw('AVG(actual_time - estimated_time) as avg_delay'))
                ->value('avg_delay');

            $avgActual = $avgActual !== null ? round($avgActual, 1) : null;
            $avgEstimated = $avgEstimated !== null ? round($avgEstimated, 1) : null;
            $avgDelay = $avgDelay !== null ? round((float) $avgDelay, 1) : null;

            if ($avgDelay !== null) {
                $summary = 'Average delay '.$avgDelay.' minutes.';
            } else {
                $summary = 'No delay data available.';
            }

            return [
                'meta' => [
                    'user_id' => $user->id,
                    'cached' => false,
                    'generated_at' => Carbon::now()->toIso8601String(),
                ],
                'data' => [
                    'completed_count' => $completedCount,
                    'avg_actual_time' => $avgActual,
                    'avg_estimated_time' => $avgEstimated,
                    'avg_delay_minutes' => $avgDelay,
                ],
                'summary' => $summary,
            ];
        });

        return $payload;
    }

    /**
     * @return array{
     *     meta: array{task_id: int, cached: bool, generated_at: string},
     *     data: array{
     *         estimated_time: int|null,
     *         predicted_time: int|null,
     *         basis: string,
     *         samples: int,
     *         factor: float|null,
     *     },
     *     summary: string
     * }
     */
    public function taskEstimatedTimeOfArrivalBasic(Task $task): array
    {
        $cacheKey = "analytics:task_estimated_time_of_arrival:{$task->id}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($task) {
            $estimated = $task->estimated_time;

            if ($estimated === null || $estimated <= 0) {
                return [
                    'meta' => [
                        'task_id' => $task->id,
                        'cached' => false,
                        'generated_at' => Carbon::now()->toIso8601String(),
                    ],
                    'data' => [
                        'estimated_time' => $estimated,
                        'predicted_time' => null,
                        'basis' => 'none',
                        'samples' => 0,
                        'factor' => null,
                    ],
                    'summary' => 'No estimate is available for this task.',
                ];
            }

            $projectStats = Task::query()
                ->where('project_id', $task->project_id)
                ->where('status', TaskStatusEnum::DONE->value)
                ->whereNotNull('estimated_time')
                ->where('estimated_time', '>', 0)
                ->whereNotNull('actual_time')
                ->where('actual_time', '>', 0)
                ->select([
                    DB::raw('COUNT(*) as samples'),
                    DB::raw('AVG(actual_time * 1.0 / estimated_time) as factor'),
                ])
                ->first();

            $basis = 'fallback';
            $samples = 0;
            $factor = 1.0;

            if ($projectStats !== null) {
                $projectSamples = (int) ($projectStats->samples ?? 0);
                $projectFactor = $projectStats->factor !== null ? (float) $projectStats->factor : null;

                if ($projectSamples >= 3 && $projectFactor !== null && $projectFactor > 0) {
                    $basis = 'project';
                    $samples = $projectSamples;
                    $factor = $projectFactor;
                }
            }

            if ($basis === 'fallback') {
                $globalStats = Task::query()
                    ->where('status', TaskStatusEnum::DONE->value)
                    ->whereNotNull('estimated_time')
                    ->where('estimated_time', '>', 0)
                    ->whereNotNull('actual_time')
                    ->where('actual_time', '>', 0)
                    ->select([
                        DB::raw('COUNT(*) as samples'),
                        DB::raw('AVG(actual_time * 1.0 / estimated_time) as factor'),
                    ])
                    ->first();

                if ($globalStats !== null) {
                    $globalSamples = (int) ($globalStats->samples ?? 0);
                    $globalFactor = $globalStats->factor !== null ? (float) $globalStats->factor : null;

                    if ($globalSamples > 0 && $globalFactor !== null && $globalFactor > 0) {
                        $basis = 'global';
                        $samples = $globalSamples;
                        $factor = $globalFactor;
                    }
                }
            }

            $predicted = (int) round($estimated * $factor);
            $factorValue = round($factor, 3);

            $summary = 'Predicted time equals the estimate.';
            if ($estimated > 0) {
                $diff = $predicted - $estimated;
                if ($diff !== 0) {
                    $percent = round(abs($diff) / $estimated * 100, 1);
                    $direction = $diff > 0 ? 'more' : 'less';
                    $summary = 'predicted time is '.$percent.'% '.$direction.' than the estimate.';
                }
            }

            return [
                'meta' => [
                    'task_id' => $task->id,
                    'cached' => false,
                    'generated_at' => Carbon::now()->toIso8601String(),
                ],
                'data' => [
                    'estimated_time' => $estimated,
                    'predicted_time' => $predicted,
                    'basis' => $basis,
                    'samples' => $samples,
                    'factor' => $basis === 'fallback' ? 1.0 : $factorValue,
                ],
                'summary' => $summary,
            ];
        });

        return $payload;
    }
}
