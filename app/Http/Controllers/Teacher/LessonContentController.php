<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\ResolvesLessonPlacement;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonVersion;
use App\Models\Section;
use App\Models\SubSection;
use App\Services\CourseAccessService;
use App\Services\LessonContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonContentController extends Controller
{
    use ResolvesLessonPlacement;

    public function __construct(
        protected CourseAccessService $access,
        protected LessonContentService $content
    ) {}

    protected function authorizeLesson(Course $course, Section $section, Lesson $lesson, ?SubSection $subSection = null): void
    {
        if (! $this->access->canAccessCourse(auth()->user(), $course)) {
            abort(404);
        }

        $this->ensureSectionInCourse($course, $section);

        if ($subSection) {
            $this->ensureSubSectionInSection($section, $subSection);
            $this->assertLessonBelongsToSubSection($lesson, $subSection);
        } else {
            $this->assertLessonBelongsToSection($lesson, $section);
        }

        $this->authorize('update', $lesson);
    }

    public function autosaveSection(Request $request, Course $course, Section $section, Lesson $lesson): JsonResponse
    {
        return $this->performAutosave($request, $course, $section, $lesson, null);
    }

    public function autosave(Request $request, Course $course, Section $section, SubSection $subSection, Lesson $lesson): JsonResponse
    {
        return $this->performAutosave($request, $course, $section, $lesson, $subSection);
    }

    protected function performAutosave(Request $request, Course $course, Section $section, Lesson $lesson, ?SubSection $subSection): JsonResponse
    {
        $this->authorizeLesson($course, $section, $lesson, $subSection);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'main_content' => ['nullable', 'string'],
            'teacher_notes' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->content->save($lesson, auth()->user(), $validated);

            return response()->json([
                'ok' => true,
                'message' => 'تم الحفظ',
                'saved_at' => $result['saved_at'],
                'saved_at_formatted' => \Carbon\Carbon::parse($result['saved_at'])->timezone(config('app.timezone'))->format('H:i:s'),
                'version_id' => $result['version_id'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'فشل الحفظ',
            ], 500);
        }
    }

    public function showVersionSection(Course $course, Section $section, Lesson $lesson, LessonVersion $version): JsonResponse
    {
        return $this->performShowVersion($course, $section, $lesson, $version, null);
    }

    public function showVersion(Course $course, Section $section, SubSection $subSection, Lesson $lesson, LessonVersion $version): JsonResponse
    {
        return $this->performShowVersion($course, $section, $lesson, $version, $subSection);
    }

    protected function performShowVersion(Course $course, Section $section, Lesson $lesson, LessonVersion $version, ?SubSection $subSection): JsonResponse
    {
        $this->authorizeLesson($course, $section, $lesson, $subSection);
        abort_unless($version->lesson_id === $lesson->id, 404);

        $version->load('author:id,name');

        return response()->json([
            'id' => $version->id,
            'title' => $version->title,
            'objective' => $version->objective,
            'main_content' => $version->main_content,
            'teacher_notes' => $version->teacher_notes,
            'created_at' => $version->created_at->toIso8601String(),
            'created_at_formatted' => $version->created_at->format('Y-m-d H:i'),
            'author' => $version->author?->name,
        ]);
    }

    public function restoreSection(Request $request, Course $course, Section $section, Lesson $lesson, LessonVersion $version): JsonResponse
    {
        return $this->performRestore($course, $section, $lesson, $version, null);
    }

    public function restore(Request $request, Course $course, Section $section, SubSection $subSection, Lesson $lesson, LessonVersion $version): JsonResponse
    {
        return $this->performRestore($course, $section, $lesson, $version, $subSection);
    }

    protected function performRestore(Course $course, Section $section, Lesson $lesson, LessonVersion $version, ?SubSection $subSection): JsonResponse
    {
        $this->authorizeLesson($course, $section, $lesson, $subSection);
        abort_unless($version->lesson_id === $lesson->id, 404);

        try {
            $this->content->restoreVersion($lesson->fresh(), $version, auth()->user());
            $lesson->refresh();

            return response()->json([
                'ok' => true,
                'message' => 'تم استعادة النسخة',
                'lesson' => $lesson->only(['title', 'objective', 'main_content', 'teacher_notes', 'status']),
                'saved_at_formatted' => $lesson->updated_at->format('H:i:s'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'فشل الاستعادة'], 500);
        }
    }
}
