<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ProjectFilterRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProjectController extends Controller
{
    public function index(ProjectFilterRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $page = max((int) $request->query('page', 1), 1);
        $cacheKey = $this->makeIndexCacheKey($validated, $page);

        $projects = Cache::remember($cacheKey, 300, function () use ($validated, $page) {
            $query = Project::query()->with(['creator']);

            if ($search = $validated['q'] ?? null) {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            }

            foreach (['status', 'priority', 'type', 'created_by'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $query->where($field, $validated[$field]);
                }
            }

            if (array_key_exists('recurring', $validated)) {
                $query->where('recurring', $validated['recurring']);
            }

            if ($startDate = $validated['start_date'] ?? null) {
                $query->whereDate('start_date', '>=', $startDate);
            }

            if ($endDate = $validated['end_date'] ?? null) {
                $query->whereDate('end_date', '<=', $endDate);
            }

            $sort = $validated['sort'] ?? '-created_at';

            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $column = ltrim($sort, '-');

            $sortables = [
                'created_at',
                'start_date',
                'end_date',
                'title',
                'priority',
                'status',
            ];

            if (! in_array($column, $sortables, true)) {
                $column = 'created_at';
                $direction = 'desc';
            }

            $query->orderBy($column, $direction);

            $perPage = $validated['per_page'] ?? null;

            return $query
                ->paginate($perPage, ['*'], 'page', $page)
                ->withQueryString();
        });

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = $request->user()?->id;

            $project = Project::create($data);

            $project->load(['creator']);

            return new ProjectResource($project, Response::HTTP_CREATED);
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'unexpected error occurred.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Project $project, UpdateProjectRequest $request)
    {
        try {
            $data = $request->validated();
            unset($data['created_by']);

            $project->fill($data);
            $project->save();

            $project->load(['creator']);

            return new ProjectResource($project, Response::HTTP_OK);
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'unexpected error occurred.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Project $project)
    {
        try {
            $project->delete();

            return response()->noContent();
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'unexpected error occurred.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function makeIndexCacheKey(array $validated, int $page): string
    {
        ksort($validated);

        return 'projects:index:'.md5(json_encode([
            'filters' => $validated,
            'page' => $page,
        ]));
    }
}
