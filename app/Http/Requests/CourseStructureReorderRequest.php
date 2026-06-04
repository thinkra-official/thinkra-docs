<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseStructureReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['required', 'integer', 'min:1'],
            'sections.*.position' => ['required', 'integer', 'min:1'],
            'sections.*.lessons' => ['sometimes', 'array'],
            'sections.*.lessons.*.id' => ['required', 'integer', 'min:1'],
            'sections.*.lessons.*.position' => ['required', 'integer', 'min:1'],
            'sections.*.subSections' => ['sometimes', 'array'],
            'sections.*.subSections.*.id' => ['required', 'integer', 'min:1'],
            'sections.*.subSections.*.position' => ['required', 'integer', 'min:1'],
            'sections.*.subSections.*.lessons' => ['sometimes', 'array'],
            'sections.*.subSections.*.lessons.*.id' => ['required', 'integer', 'min:1'],
            'sections.*.subSections.*.lessons.*.position' => ['required', 'integer', 'min:1'],
        ];
    }
}
