<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Services\Docs\DocsAccessService;
use App\Services\Docs\DocsLessonProgressService;
use App\Services\Docs\DocsReaderService;
use App\Services\Docs\DocsTocParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocsLessonController extends Controller
{
    public function __construct(
        protected DocsReaderService $reader,
        protected DocsAccessService $access,
        protected DocsTocParser $toc,
        protected DocsLessonProgressService $progress
    ) {}

    public function show(Request $request, string $courseSlug, string $lessonSlug, bool $preview = false): View|RedirectResponse
    {
        $course = $this->reader->courseBySlug($courseSlug);

        if (! $course) {
            abort(404);
        }

        $user = $request->user();

        if ($course->require_login && $user === null && ! $preview) {
            return redirect()->guest(route('teacher.login', ['redirect' => $request->fullUrl()]));
        }

        if (! $this->access->canViewCourse($course, $user, $request, $preview)) {
            abort(404);
        }

        $lesson = $this->reader->findLessonInCourse($course, $lessonSlug, $preview);

        if (! $lesson || ! $this->access->canViewLesson($lesson, $user, $request, $preview)) {
            abort(404);
        }

        $structure = $this->reader->loadCourseStructure($course, $preview);
        $adjacent = $this->reader->adjacentLessons($structure['flatLessons'], $lesson);

        $contentHtml = $this->toc->injectHeadingIds($lesson->main_content ?? '');
        $toc = $this->toc->parse($lesson->main_content ?? '');

        $showNotes = $this->access->canShowTeacherNotes($user, $course, $preview);

        $lessonRoute = $preview
            ? route('docs.preview.lesson', [$course->slug, $lesson->slug])
            : route('docs.lesson', [$course->slug, $lesson->slug]);

        $metaDescription = \Illuminate\Support\Str::limit(strip_tags($lesson->objective ?: $lesson->main_content ?? ''), 160);
        $enableSeo = $course->isPublic() && ! $preview;
        $placement = $this->reader->lessonPlacement($lesson, $structure['sections']);
        $progressStats = $this->progress->statsForUser($user, $structure['flatLessons']);
        $expanded = $this->reader->defaultExpandedState($structure['sections'], $lesson);
        $readingMinutes = $this->progress->estimateReadingMinutes(
            $lesson->main_content,
            $lesson->objective
        );
        $lessonComplete = $this->progress->isComplete($user, $lesson, $progressStats['completedIds']);

        return view('docs.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'currentLesson' => $lesson,
            'lessonSection' => $placement['section'],
            'lessonSubSection' => $placement['subSection'],
            'sections' => $structure['sections'],
            'flatLessons' => $structure['flatLessons'],
            'previousLesson' => $adjacent['previous'],
            'nextLesson' => $adjacent['next'],
            'contentHtml' => $contentHtml,
            'toc' => $toc,
            'preview' => $preview,
            'showTeacherNotes' => $showNotes && filled($lesson->teacher_notes),
            'progressStats' => $progressStats,
            'expandedSections' => $expanded['expandedSections'],
            'expandedSubSections' => $expanded['expandedSubSections'],
            'readingMinutes' => $readingMinutes,
            'lessonComplete' => $lessonComplete,
            'canTrackProgress' => $user !== null && ! $preview,
            'completeUrl' => route('docs.lesson.complete', [$course->slug, $lesson->slug]),
            'seo' => $enableSeo,
            'metaDescription' => $metaDescription,
            'canonicalUrl' => $lessonRoute,
            'ogTitle' => $lesson->title.' — '.$course->title,
        ]);
    }
}
