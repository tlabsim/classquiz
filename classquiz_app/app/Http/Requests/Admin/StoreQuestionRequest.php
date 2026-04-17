<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTeacher();
    }

    public function rules(): array
    {
        return [
            'type'                      => ['required', Rule::in(['tf', 'mcq_single', 'mcq_multi'])],
            'text'                      => ['required', 'string', 'max:20000'],
            'points'                    => ['required', 'numeric', 'min:0', 'max:9999'],
            'sort_order'                => ['nullable', 'integer', 'min:0'],
            'settings'                  => ['nullable', 'array'],
            'settings.randomize_choices' => ['nullable', 'boolean'],
            'settings.rich_choices'      => ['nullable', 'boolean'],
            'settings.mcq_multi_grading' => ['nullable', Rule::in([
                'all_or_nothing',
                'partial_with_deduction',
                'partial_without_deduction',
            ])],
            'choices'                   => ['nullable', 'array', 'max:10'],
            'choices.*.text'            => ['nullable', 'string', 'max:20000'],
            'choices.*.is_correct'      => ['nullable', 'boolean'],
            'choices.*.sort_order'      => ['nullable', 'integer', 'min:0'],
            'tf_correct'                => ['nullable', Rule::in(['true', 'false'])],
            'existing_tag'              => ['nullable', 'string', 'max:120'],
            'new_tag'                   => ['nullable', 'string', 'max:120'],
            'tag'                       => ['nullable', 'string', 'max:120'],
            'feedback_correct'          => ['nullable', 'string', 'max:5000'],
            'feedback_incorrect'        => ['nullable', 'string', 'max:5000'],
            'explanation'               => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'text' => 'question body',
            'points' => 'points',
            'choices.*.text' => 'answer choice',
            'tf_correct' => 'correct answer',
            'existing_tag' => 'tag',
            'new_tag' => 'tag',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Enter the question body.',
            'points.required' => 'Enter the points for this question.',
            'choices.*.text.required' => 'Each answer choice needs text.',
            'tf_correct.in' => 'Choose whether True or False is correct.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $choices = collect($this->input('choices', []))
            ->map(function ($choice, $index) {
                return [
                    'text' => $choice['text'] ?? '',
                    'is_correct' => filter_var($choice['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'sort_order' => $choice['sort_order'] ?? $index,
                ];
            })
            ->all();

        $this->merge([
            'choices' => $choices,
            'settings' => array_merge(
                $this->input('settings', []),
                [
                    'randomize_choices' => filter_var(
                        data_get($this->input('settings', []), 'randomize_choices', false),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    'rich_choices' => filter_var(
                        data_get($this->input('settings', []), 'rich_choices', false),
                        FILTER_VALIDATE_BOOLEAN
                    ),
                    'mcq_multi_grading' => data_get(
                        $this->input('settings', []),
                        'mcq_multi_grading',
                        'all_or_nothing'
                    ),
                ]
            ),
            'existing_tag' => trim((string) $this->input('existing_tag', '')),
            'new_tag' => trim((string) $this->input('new_tag', '')),
            'tag' => trim((string) ($this->input('new_tag') ?: $this->input('existing_tag', ''))),
        ]);
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $plainQuestionText = trim(preg_replace('/\xc2\xa0|&nbsp;/', ' ', strip_tags((string) $this->input('text'))));

                if ($plainQuestionText === '') {
                    $validator->errors()->add('text', 'Enter the question body.');
                    return;
                }

                if ($this->input('type') === 'tf' && ! $this->filled('tf_correct')) {
                    $validator->errors()->add('tf_correct', 'Choose whether True or False is correct.');
                }

                if (in_array($this->input('type'), ['mcq_single', 'mcq_multi'], true)) {
                    $choices = collect($this->input('choices', []));
                    $filledChoices = $choices->filter(fn ($choice) => filled($choice['text'] ?? null));
                    $hasCorrect = $choices->contains(fn ($choice) => ! empty($choice['is_correct']));
                    $hasEnoughChoices = $filledChoices->count() >= 2;

                    if (! $hasEnoughChoices) {
                        $validator->errors()->add('choices', 'Add at least two answer choices.');
                        return;
                    }

                    if ($filledChoices->count() !== $choices->count()) {
                        $validator->errors()->add('choices', 'Each answer choice needs text.');
                    } elseif (! $hasCorrect) {
                        $validator->errors()->add('choices', 'Mark at least one correct answer.');
                    } elseif ($this->input('type') === 'mcq_single' && $choices->where('is_correct', true)->count() > 1) {
                        $validator->errors()->add('choices', 'Single-answer questions can only have one correct choice.');
                    }
                }
            },
        ];
    }
}
