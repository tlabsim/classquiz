<?php

namespace App\Http\Requests\Public;

use App\Models\QuizAssignment;
use Illuminate\Foundation\Http\FormRequest;

class RegisterForQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assignment = QuizAssignment::findByPublicToken((string) $this->route('token'));

        $collectName = (bool) $assignment?->setting('collect_name', false);
        $collectClassId = (bool) $assignment?->setting('collect_class_id', false);
        $requiresAccessCode = (bool) $assignment?->access_code_required;

        return [
            'email'    => ['required', 'email', 'max:255'],
            'name'     => [$collectName ? 'nullable' : 'prohibited', 'string', 'max:255'],
            'class_id' => [$collectClassId ? 'nullable' : 'prohibited', 'string', 'max:100'],
            'session_id' => ['nullable', 'string'],
            'code' => [$requiresAccessCode ? 'required' : 'nullable', 'string', 'size:6'],
        ];
    }
}
