<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', new Enum(TaskStatusEnum::class)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'estimated_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'actual_time' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->hasAnyUpdateableField()) {
                $validator->errors()->add('data', 'No updatable fields were provided.');
            }
        });
    }

    private function hasAnyUpdateableField(): bool
    {
        $fields = [
            'title',
            'description',
            'status',
            'assigned_to',
            'due_date',
            'tags',
            'estimated_time',
            'actual_time',
        ];

        foreach ($fields as $field) {
            if ($this->has($field)) {
                return true;
            }
        }

        return false;
    }
}
