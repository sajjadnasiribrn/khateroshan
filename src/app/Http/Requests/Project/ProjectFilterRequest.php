<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectPriorityEnum;
use App\Enums\ProjectStatusEnum;
use App\Enums\ProjectTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProjectFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', new Enum(ProjectStatusEnum::class)],
            'priority' => ['sometimes', new Enum(ProjectPriorityEnum::class)],
            'type' => ['sometimes', new Enum(ProjectTypeEnum::class)],
            'recurring' => ['sometimes', 'boolean'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'q' => ['sometimes', 'string'],
            'sort' => ['sometimes', 'string'],
            'page' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('recurring')) {
            $this->merge([
                'recurring' => filter_var($this->input('recurring'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        if (array_key_exists('recurring', $validated)) {
            $validated['recurring'] = (bool) $validated['recurring'];
        }

        if (array_key_exists('per_page', $validated)) {
            $validated['per_page'] = (int) $validated['per_page'];
        }

        return $validated;
    }
}
