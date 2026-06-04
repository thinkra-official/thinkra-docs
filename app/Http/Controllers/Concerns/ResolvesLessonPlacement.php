<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\SubSection;

trait ResolvesLessonPlacement
{
    protected function assertLessonBelongsToSection(Lesson $lesson, Section $section): void
    {
        abort_unless($lesson->isDirectInSection() && $lesson->section_id === $section->id, 404);
    }

    protected function assertLessonBelongsToSubSection(Lesson $lesson, SubSection $subSection): void
    {
        abort_unless($lesson->isInSubSection() && $lesson->sub_section_id === $subSection->id, 404);
    }

    protected function ensureSectionInCourse(Course $course, Section $section): void
    {
        abort_unless($section->course_id === $course->id, 404);
    }

    protected function ensureSubSectionInSection(Section $section, SubSection $subSection): void
    {
        abort_unless($subSection->section_id === $section->id, 404);
    }

    protected function lessonCourse(Lesson $lesson): Course
    {
        return $lesson->resolveCourse();
    }
}
