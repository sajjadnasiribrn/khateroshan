<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * @mixin \App\Models\Project
 */
class ProjectResource extends BaseJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->enumValue($this->status),
            'priority' => $this->enumValue($this->priority),
            'type' => $this->enumValue($this->type),
            'recurring' => (bool) $this->recurring,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'created_by' => $this->created_by,
            'budget' => $this->budget,
            'attachments' => $this->attachments,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'tasks' => $this->whenLoaded('tasks', fn () => TaskResource::collection($this->tasks)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
