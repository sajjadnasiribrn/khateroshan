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
    /**
     * @group Projects
     * @authenticated
     *
     * List projects with optional filters and pagination.
     *
     * @queryParam q string Search term applied to title and description. Example: sprint
     * @queryParam status string Filter by status enum value. Example: active
     * @queryParam priority string Filter by priority enum value. Example: high
     * @queryParam type string Filter by project type enum value. Example: internal
     * @queryParam created_by integer Filter by creator user id. Example: 5
     * @queryParam recurring boolean Filter by recurring flag (0 or 1). Example: 1
     * @queryParam start_date date Include projects starting on or after the provided date. Example: 2025-01-01
     * @queryParam end_date date Include projects ending on or before the provided date. Example: 2025-12-31
     * @queryParam sort string Sort column prefixed with "-" for descending. Example: -created_at
     * @queryParam per_page integer Items per page, defaults to the application's pagination value. Example: 20
     */
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

    /**
     * @group Projects
     * @authenticated
     *
     * Create a new project.
     *
     * The authenticated user is automatically stored as the creator.
     *
     * @bodyParam title string required Project title. Example: Marketing launch
     * @bodyParam description string Project description text. Example: Coordinate launch tasks
     * @bodyParam status string Project status enum value. Example: active
     * @bodyParam priority string Project priority enum value. Example: high
     * @bodyParam type string Project type enum value. Example: internal
     * @bodyParam start_date date Project start date. Example: 2025-03-15
     * @bodyParam end_date date Project end date. Example: 2025-07-30
     * @bodyParam recurring boolean Whether the project repeats. Example: false
     * @bodyParam budget number Project budget in the application's currency. Example: 15000.50
     * @bodyParam attachments array Attachment identifiers. Example: ["file_1","file_2"]
     */
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

    /**
     * @group Projects
     * @authenticated
     *
     * Update an existing project.
     *
     * The authenticated user remains the creator; sending `created_by` is ignored.
     *
     * @bodyParam title string Project title. Example: Marketing launch
     * @bodyParam description string Project description text. Example: Coordinate launch tasks
     * @bodyParam status string Project status enum value. Example: on_hold
     * @bodyParam priority string Project priority enum value. Example: medium
     * @bodyParam type string Project type enum value. Example: client
     * @bodyParam start_date date Project start date. Example: 2025-03-15
     * @bodyParam end_date date Project end date. Example: 2025-07-30
     * @bodyParam recurring boolean Whether the project repeats. Example: false
     * @bodyParam budget number Project budget in the application's currency. Example: 15000.50
     * @bodyParam attachments array Attachment identifiers. Example: ["file_1","file_2"]
     */
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

    /**
     * @group Projects
     * @authenticated
     *
     * Delete a project permanently.
     */
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
