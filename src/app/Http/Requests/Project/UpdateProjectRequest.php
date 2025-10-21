<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectPriorityEnum;
use App\Enums\ProjectStatusEnum;
use App\Enums\ProjectTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', new Enum(ProjectStatusEnum::class)],
            'priority' => ['sometimes', 'nullable', new Enum(ProjectPriorityEnum::class)],
            'type' => ['sometimes', 'nullable', new Enum(ProjectTypeEnum::class)],
            'start_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'recurring' => ['sometimes', 'nullable', 'boolean'],
            'budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'attachments' => ['sometimes', 'nullable', 'array'],
            'created_by' => ['prohibited'],
        ];
    }
}

