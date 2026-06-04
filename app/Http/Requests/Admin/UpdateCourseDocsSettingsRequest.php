<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseDocsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visibility' => ['required', Rule::enum(CourseVisibility::class)],
            'require_login' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'require_login' => $this->boolean('require_login'),
        ]);
    }
}
