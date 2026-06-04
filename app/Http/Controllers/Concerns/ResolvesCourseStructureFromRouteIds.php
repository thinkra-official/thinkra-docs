<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use App\Models\Lesson;
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

    /**
     * @return array{0: Course, 1: Section, 2: Lesson}
     */
    protected function resolveDirectSectionLesson(
        int|string $courseId,
        int|string $sectionId,
        int|string $lessonId
    ): array {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        $lesson = Lesson::query()
            ->where('section_id', $section->id)
            ->whereNull('sub_section_id')
            ->whereKey($lessonId)
            ->firstOrFail();

        return [$course, $section, $lesson];
    }

    /**
     * @return array{0: Course, 1: Section, 2: SubSection, 3: Lesson}
     */
    protected function resolveSubSectionLesson(
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId,
        int|string $lessonId
    ): array {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);
        $subSection = $this->resolveSubSectionInSection($section, $subSectionId);

        $lesson = Lesson::query()
            ->where('sub_section_id', $subSection->id)
            ->whereNull('section_id')
            ->whereKey($lessonId)
            ->firstOrFail();

        return [$course, $section, $subSection, $lesson];
    }
}
