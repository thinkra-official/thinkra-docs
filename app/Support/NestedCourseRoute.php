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
            'courseId' => $course->id,
            'sectionId' => $section->id,
        ];
    }

    public static function sectionDestroy(Course $course, Section $section): array
    {
        return self::section($course, $section);
    }

    public static function subSectionDestroy(Course $course, Section $section, SubSection $subSection): array
    {
        return [
            'courseId' => $course->id,
            'sectionId' => $section->id,
            'subSectionId' => $subSection->id,
        ];
    }

    public static function subSection(Course $course, Section $section, SubSection $subSection): array
    {
        return self::subSectionDestroy($course, $section, $subSection);
    }

    public static function sectionLesson(Course $course, Section $section, Lesson $lesson): array
    {
        return [
            'courseId' => $course->id,
            'sectionId' => $section->id,
            'lessonId' => $lesson->id,
        ];
    }

    public static function sectionLessonDestroy(Course $course, Section $section, Lesson $lesson): array
    {
        return self::sectionLesson($course, $section, $lesson);
    }

    public static function subSectionLesson(
        Course $course,
        Section $section,
        SubSection $subSection,
        Lesson $lesson
    ): array {
        return [
            'courseId' => $course->id,
            'sectionId' => $section->id,
            'subSectionId' => $subSection->id,
            'lessonId' => $lesson->id,
        ];
    }

    public static function subSectionLessonDestroy(
        Course $course,
        Section $section,
        SubSection $subSection,
        Lesson $lesson
    ): array {
        return self::subSectionLesson($course, $section, $subSection, $lesson);
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

    /** @deprecated Use subSectionLesson() */
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
            'versionId' => $version instanceof LessonVersion ? $version->id : $version,
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
            'versionId' => $version instanceof LessonVersion ? $version->id : $version,
        ];
    }
}
