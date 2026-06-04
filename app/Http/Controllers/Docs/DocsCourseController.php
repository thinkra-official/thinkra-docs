<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Services\Docs\DocsAccessService;
use App\Services\Docs\DocsLessonProgressService;
use App\Services\Docs\DocsReaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocsCourseController extends Controller
{
    public function __construct(
        protected DocsReaderService $reader,
        protected DocsAccessService $access,
        protected DocsLessonProgressService $progress
    ) {}

    public function show(Request $request, string $courseSlug, bool $preview = false): View|RedirectResponse
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

        $structure = $this->reader->loadCourseStructure($course, $preview);
        $first = $structure['flatLessons']->first();

        if ($first) {
            $route = $preview ? 'docs.preview.lesson' : 'docs.lesson';

            return redirect()->route($route, [$course->slug, $first->slug]);
        }

        $progressStats = $this->progress->statsForUser($user, $structure['flatLessons']);

        return view('docs.course-empty', [
            'course' => $course,
            'sections' => $structure['sections'],
            'preview' => $preview,
            'currentLesson' => null,
            'progressStats' => $progressStats,
            'completedLessonIds' => $progressStats['completedIds'],
            'expandedSections' => $structure['sections']->isNotEmpty()
                ? [$structure['sections']->first()->id]
                : [],
            'expandedSubSections' => [],
        ]);
    }
}
