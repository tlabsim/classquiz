<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
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
            'availability_start'        => ['nullable', 'date'],
            'availability_end'          => ['nullable', 'date', 'after_or_equal:availability_start'],
            'access_code_required'      => ['boolean'],
            'access_code_starts_before_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'duration_minutes'          => ['nullable', 'integer', 'min:1', 'max:480'],
            'settings'                  => ['nullable', 'array'],
            'settings.allow_resume'     => ['nullable', 'boolean'],
            'settings.show_score'       => ['nullable', 'boolean'],
            'settings.randomize_questions' => ['nullable', 'boolean'],
            'settings.question_presentation' => ['nullable', 'in:one_per_page,all_in_same_page'],
            'settings.allow_modify_previous_answers' => ['nullable', 'boolean'],
            'settings.collect_email'    => ['nullable', 'boolean'],
            'settings.collect_class_id' => ['nullable', 'boolean'],
            'settings.collect_name'     => ['nullable', 'boolean'],
            'settings.timezone'         => ['nullable', 'timezone'],
            'settings.show_correct_answers' => ['nullable', 'boolean'],
            'settings.show_feedback_and_explanation' => ['nullable', 'boolean'],
            'settings.max_attempts'     => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $settings = array_merge(
            \App\Models\QuizAssignment::SETTINGS_DEFAULTS,
            $this->input('settings', [])
        );

        if (($settings['question_presentation'] ?? null) !== 'one_per_page') {
            $settings['allow_modify_previous_answers'] = false;
        }

        if (!($settings['show_score'] ?? false)) {
            $settings['show_correct_answers'] = false;
            $settings['show_feedback_and_explanation'] = false;
        }

        if (!($settings['show_correct_answers'] ?? false)) {
            $settings['show_feedback_and_explanation'] = false;
        }

        $settings['collect_email'] = true;
        $settings['timezone'] = $this->normalizeTimezone($settings['timezone'] ?? null);

        $this->merge([
            'settings' => $settings,
            'availability_start' => $this->normalizeAssignmentDateTime($this->input('availability_start'), $settings['timezone']),
            'availability_end' => $this->normalizeAssignmentDateTime($this->input('availability_end'), $settings['timezone']),
        ]);
    }

    private function normalizeTimezone(?string $timezone): string
    {
        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('app.timezone', 'UTC');
    }

    private function normalizeAssignmentDateTime(?string $value, string $timezone): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value, $timezone)
                ->utc()
                ->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $value;
        }
    }
}
