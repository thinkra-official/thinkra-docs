<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'main_content' => ['nullable', 'string'],
            'teacher_notes' => ['nullable', 'string'],
        ];
    }
}
