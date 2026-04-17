<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher();
    }

    public function rules(): array
    {
        return [
            'title'                     => ['nullable', 'string', 'max:255'],
            'instructions'              => ['nullable', 'string', 'max:10000'],
            'is_active'                 => ['boolean'],
            'registration_start'        => ['nullable', 'date'],
            'registration_end'          => ['nullable', 'date', 'after_or_equal:registration_start'],
            'availability_start'        => ['nullable', 'date'],
            'availability_end'          => ['nullable', 'date', 'after_or_equal:availability_start'],
            'duration_minutes'          => ['nullable', 'integer', 'min:1', 'max:480'],
            'settings'                  => ['nullable', 'array'],
            'settings.allow_resume'     => ['nullable', 'boolean'],
            'settings.show_score'       => ['nullable', 'boolean'],
            'settings.randomize_questions' => ['nullable', 'boolean'],
            'settings.max_attempts'     => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
