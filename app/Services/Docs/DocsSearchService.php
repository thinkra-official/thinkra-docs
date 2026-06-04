<?php

namespace App\Services\Docs;

use App\Enums\CourseVisibility;
use App\Enums\LessonStatus;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DocsSearchService
{
    /**
     * @return Collection<int, array{type: string, title: string, excerpt: string, url: string}>
     */
    public function search(string $query, int $limit = 30): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $like = '%'.$query.'%';
        $results = collect();

        $courses = Course::query()
            ->where('visibility', CourseVisibility::Public)
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->limit(10)
            ->get();

        foreach ($courses as $course) {
            $results->push([
                'type' => 'course',
                'title' => $course->title,
                'courseTitle' => '',
                'excerpt' => Str::limit(strip_tags($course->description ?? ''), 120),
                'url' => route('docs.course', $course->slug),
            ]);
        }

        $sections = \App\Models\Section::query()
            ->whereHas('course', fn ($q) => $q->where('visibility', CourseVisibility::Public))
            ->where('title', 'like', $like)
            ->with('course')
            ->limit(15)
            ->get();

        foreach ($sections as $section) {
            $first = app(DocsReaderService::class)->firstLesson($section->course);
            $results->push([
                'type' => 'section',
                'title' => $section->title,
                'courseTitle' => $section->course->title,
                'excerpt' => '',
                'url' => $first
                    ? route('docs.lesson', [$section->course->slug, $first->slug])
                    : route('docs.course', $section->course->slug),
            ]);
        }

        $subSections = \App\Models\SubSection::query()
            ->where('title', 'like', $like)
            ->whereHas('section.course', fn ($q) => $q->where('visibility', CourseVisibility::Public))
            ->with('section.course')
            ->limit(15)
            ->get();

        foreach ($subSections as $sub) {
            $course = $sub->section->course;
            $first = $sub->lessons()->published()->orderBy('sort_order')->first();
            $results->push([
                'type' => 'sub_section',
                'title' => $sub->title,
                'courseTitle' => $course->title,
                'excerpt' => '',
                'url' => $first
                    ? route('docs.lesson', [$course->slug, $first->slug])
                    : route('docs.course', $course->slug),
            ]);
        }

        $lessons = Lesson::query()
            ->published()
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('objective', 'like', $like)
                    ->orWhere('main_content', 'like', $like);
            })
            ->where(function ($q) {
                $q->whereHas('section.course', fn ($sq) => $sq->where('visibility', CourseVisibility::Public))
                    ->orWhereHas('subSection.section.course', fn ($sq) => $sq->where('visibility', CourseVisibility::Public));
            })
            ->with(['section.course', 'subSection.section.course'])
            ->limit($limit)
            ->get();

        foreach ($lessons as $lesson) {
            $course = $lesson->resolveCourse();
            $results->push([
                'type' => 'lesson',
                'title' => $lesson->title,
                'courseTitle' => $course->title,
                'excerpt' => Str::limit(strip_tags($lesson->objective ?: $lesson->main_content ?? ''), 140),
                'url' => route('docs.lesson', [$course->slug, $lesson->slug]),
            ]);
        }

        return $results->unique('url')->take($limit)->values();
    }
}
