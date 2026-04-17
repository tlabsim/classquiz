<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class RegisterForQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'max:255'],
            'name'     => ['nullable', 'string', 'max:255'],
            'class_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
