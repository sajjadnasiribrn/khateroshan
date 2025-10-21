<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectPriorityEnum;
use App\Enums\ProjectStatusEnum;
use App\Enums\ProjectTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(ProjectStatusEnum::class)],
            'priority' => ['nullable', new Enum(ProjectPriorityEnum::class)],
            'type' => ['nullable', new Enum(ProjectTypeEnum::class)],
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'recurring' => ['nullable', 'boolean'],
            'created_by' => ['required', 'integer', 'exists:users,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($user = $this->user()) {
            $this->merge([
                'created_by' => $user->id,
            ]);
        }
    }
}
