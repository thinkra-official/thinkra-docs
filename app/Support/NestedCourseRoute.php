<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Section;
use App\Models\SubSection;

final class NestedCourseRoute
{
    public static function section(Course $course, Section $section): array
    {
        return [
            'course' => $course,
            'section' => $section,
        ];
    }

    public static function subSection(Course $course, Section $section, SubSection $subSection): array
    {
        return [
            'course' => $course,
            'section' => $section,
            'subSection' => $subSection,
        ];
    }

    /** درس مباشرة تحت الفصل */
    public static function sectionLesson(Course $course, Section $section, Lesson $lesson): array
    {
        return [
            'course' => $course,
            'section' => $section,
            'lesson' => $lesson,
        ];
    }

    /** درس داخل قسم فرعي */
    public static function subSectionLesson(
        Course $course,
        Section $section,
        SubSection $subSection,
        Lesson $lesson
    ): array {
        return [
            'course' => $course,
            'section' => $section,
            'subSection' => $subSection,
            'lesson' => $lesson,
        ];
    }

    public static function forLesson(
        Course $course,
        Section $section,
        Lesson $lesson,
        ?SubSection $subSection = null
    ): array {
        return $subSection
            ? self::subSectionLesson($course, $section, $subSection, $lesson)
            : self::sectionLesson($course, $section, $lesson);
    }

    /** @deprecated Use sectionLesson() or subSectionLesson() */
    public static function lesson(
        Course $course,
        Section $section,
        SubSection $subSection,
        Lesson $lesson
    ): array {
        return self::subSectionLesson($course, $section, $subSection, $lesson);
    }

    public static function sectionVersion(
        Course $course,
        Section $section,
        Lesson $lesson,
        LessonVersion|int|string $version
    ): array {
        return [
            ...self::sectionLesson($course, $section, $lesson),
            'version' => $version,
        ];
    }

    public static function subSectionVersion(
        Course $course,
        Section $section,
        SubSection $subSection,
        Lesson $lesson,
        LessonVersion|int|string $version
    ): array {
        return [
            ...self::subSectionLesson($course, $section, $subSection, $lesson),
            'version' => $version,
        ];
    }
}
