<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        return $user->role->isAdmin() || $user->role === UserRole::Teacher;
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

    public function attributes(): array
    {
        return [
            'title' => 'عنوان الدرس',
        ];
    }
}
