<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers'                    => ['required', 'array'],
            'answers.*.question_id'      => ['required', 'integer', 'exists:questions,id'],
            'answers.*.selected_choice_ids' => ['nullable', 'array'],
            'answers.*.selected_choice_ids.*' => ['integer', 'exists:choices,id'],
        ];
    }
}
