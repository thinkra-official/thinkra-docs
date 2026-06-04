<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use App\Models\Section;
use App\Models\SubSection;

trait ResolvesCourseStructureFromRouteIds
{
    /**
     * @return array{0: Course, 1: Section}
     */
    protected function resolveCourseAndSection(int|string $courseId, int|string $sectionId): array
    {
        $course = Course::findOrFail($courseId);

        $section = Section::query()
            ->where('course_id', $course->id)
            ->whereKey($sectionId)
            ->firstOrFail();

        return [$course, $section];
    }

    protected function resolveSubSectionInSection(Section $section, int|string $subSectionId): SubSection
    {
        return SubSection::query()
            ->where('section_id', $section->id)
            ->whereKey($subSectionId)
            ->firstOrFail();
    }
}
