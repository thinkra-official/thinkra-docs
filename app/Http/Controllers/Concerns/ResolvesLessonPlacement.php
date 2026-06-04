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
        abort_unless((int) $lesson->section_id === (int) $section->id, 404);
        abort_if($lesson->sub_section_id !== null && (int) $lesson->sub_section_id !== 0, 404);
    }

    protected function assertLessonBelongsToSubSection(Lesson $lesson, SubSection $subSection): void
    {
        abort_unless((int) $lesson->sub_section_id === (int) $subSection->id, 404);
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
