<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * @mixin \App\Models\Task
 */
class TaskResource extends BaseJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->enumValue($this->status),
            'assigned_to' => $this->assigned_to,
            'due_date' => $this->due_date?->toDateString(),
            'tags' => $this->tags,
            'estimated_time' => $this->estimated_time,
            'actual_time' => $this->actual_time,
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'email' => $this->assignee->email,
            ]),
            'project' => $this->whenLoaded('project', fn () => new ProjectResource($this->project)),
            'comments' => $this->whenLoaded('comments', fn () => CommentResource::collection($this->comments)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
