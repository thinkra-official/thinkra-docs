<?php

namespace App\Services\Docs;

use App\Enums\LessonStatus;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Collection;

class DocsReaderService
{
    /**
     * @return array{sections: \Illuminate\Support\Collection, flatLessons: Collection<int, Lesson>}
     */
    public function loadCourseStructure(Course $course, bool $preview = false): array
    {
        $course->load([
            'sections' => fn ($q) => $q->orderBy('sort_order'),
            'sections.directLessons' => function ($q) use ($preview) {
                $q->orderBy('sort_order');
                if (! $preview) {
                    $q->where('status', LessonStatus::Published);
                }
            },
            'sections.subSections' => fn ($q) => $q->orderBy('sort_order'),
            'sections.subSections.lessons' => function ($q) use ($preview) {
                $q->orderBy('sort_order');
                if (! $preview) {
                    $q->where('status', LessonStatus::Published);
                }
            },
        ]);

        $flat = collect();

        foreach ($course->sections as $section) {
            foreach ($section->directLessons as $lesson) {
                $flat->push($lesson);
            }
            foreach ($section->subSections as $subSection) {
                foreach ($subSection->lessons as $lesson) {
                    $flat->push($lesson);
                }
            }
        }

        return [
            'sections' => $course->sections,
            'flatLessons' => $flat->values(),
        ];
    }

    public function findLessonInCourse(Course $course, string $lessonSlug, bool $preview = false): ?Lesson
    {
        $structure = $this->loadCourseStructure($course, $preview);

        return $structure['flatLessons']->first(fn (Lesson $l) => $l->slug === $lessonSlug);
    }

    public function firstLesson(Course $course, bool $preview = false): ?Lesson
    {
        $structure = $this->loadCourseStructure($course, $preview);

        return $structure['flatLessons']->first();
    }

    /**
     * @return array{previous: ?Lesson, next: ?Lesson}
     */
    public function adjacentLessons(Collection $flatLessons, Lesson $current): array
    {
        $index = $flatLessons->search(fn (Lesson $l) => $l->id === $current->id);

        if ($index === false) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $flatLessons->get($index - 1),
            'next' => $flatLessons->get($index + 1),
        ];
    }

    public function courseBySlug(string $slug): ?Course
    {
        return Course::query()->where('slug', $slug)->first();
    }
}
