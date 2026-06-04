<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Services\Docs\DocsAccessService;
use App\Services\Docs\DocsLessonProgressService;
use App\Services\Docs\DocsReaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocsLessonProgressController extends Controller
{
    public function __construct(
        protected DocsReaderService $reader,
        protected DocsAccessService $access,
        protected DocsLessonProgressService $progress
    ) {}

    public function complete(Request $request, string $courseSlug, string $lessonSlug): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'يجب تسجيل الدخول'], 401);
        }

        $course = $this->reader->courseBySlug($courseSlug);
        if (! $course || ! $this->access->canViewCourse($course, $user, $request, false)) {
            abort(404);
        }

        $lesson = $this->reader->findLessonInCourse($course, $lessonSlug, false);
        if (! $lesson || ! $this->access->canViewLesson($lesson, $user, $request, false)) {
            abort(404);
        }

        $this->progress->markComplete($user, $lesson);

        $structure = $this->reader->loadCourseStructure($course, false);
        $stats = $this->progress->statsForUser($user, $structure['flatLessons']);
        $adjacent = $this->reader->adjacentLessons($structure['flatLessons'], $lesson);

        $nextUrl = $adjacent['next']
            ? route('docs.lesson', [$course->slug, $adjacent['next']->slug])
            : null;

        return response()->json([
            'ok' => true,
            'completedIds' => $stats['completedIds'],
            'progress' => $stats,
            'nextUrl' => $nextUrl,
        ]);
    }

    public function uncomplete(Request $request, string $courseSlug, string $lessonSlug): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'يجب تسجيل الدخول'], 401);
        }

        $course = $this->reader->courseBySlug($courseSlug);
        if (! $course || ! $this->access->canViewCourse($course, $user, $request, false)) {
            abort(404);
        }

        $lesson = $this->reader->findLessonInCourse($course, $lessonSlug, false);
        if (! $lesson || ! $this->access->canViewLesson($lesson, $user, $request, false)) {
            abort(404);
        }

        $this->progress->markIncomplete($user, $lesson);

        $structure = $this->reader->loadCourseStructure($course, false);
        $stats = $this->progress->statsForUser($user, $structure['flatLessons']);

        return response()->json([
            'ok' => true,
            'completedIds' => $stats['completedIds'],
            'progress' => $stats,
        ]);
    }
}
