<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\LessonStatus;
use App\Http\Controllers\Concerns\ReordersCourseStructure;
use App\Http\Controllers\Concerns\ResolvesLessonPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreLessonRequest;
use App\Http\Requests\Teacher\UpdateLessonRequest;
use App\Models\Course;
use App\Http\Controllers\Concerns\ResolvesCourseStructureFromRouteIds;
use App\Models\Section;
use App\Services\CourseAccessService;
use App\Services\LessonContentService;
use App\Support\NestedCourseRoute;
use App\Http\Requests\StoreLessonRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionLessonController extends Controller
{
    use ReordersCourseStructure;
    use ResolvesLessonPlacement;

    public function __construct(
        protected CourseAccessService $access,
        protected LessonContentService $content
    ) {}

    protected function ensureCourseAccess(Course $course): void
    {
        if (! $this->access->canAccessCourse(auth()->user(), $course)) {
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

    public function store(StoreLessonRequest $request, Course $course, Section $section): RedirectResponse
    {
        $this->ensureCourseAccess($course);
        $this->ensureSectionInCourse($course, $section);
        $this->authorize('update', $course);

        $maxOrder = $section->directLessons()->max('sort_order') ?? 0;

    use ResolvesCourseStructureFromRouteIds;
            ...$request->validated(),
            'status' => LessonStatus::Draft,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('teacher.section-lessons.edit', NestedCourseRoute::sectionLesson($course, $section, $lesson))
            ->with('success', 'تم إنشاء الدرس.');
    }

    public function edit(Course $course, Section $section, Lesson $lesson): View
    {
        $this->ensureCourseAccess($course);
        $this->ensureSectionInCourse($course, $section);
        $this->assertLessonBelongsToSection($lesson, $section);

        $this->authorize('view', $lesson);

        $canEdit = auth()->user()->can('update', $lesson);

        $versions = $lesson->versions()->with('author:id,name')->limit(30)->get();
        $changeLogs = $lesson->changeLogs()->with('user:id,name')->limit(20)->get();

        return view('teacher.lessons.edit', [
            'course' => $course,
            'section' => $section,
            'subSection' => null,
            'lesson' => $lesson,
    public function store(StoreLessonRequest $request, int|string $courseId, int|string $sectionId): RedirectResponse
    {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        $this->ensureCourseAccess($course);
        $this->authorize('update', $course);

        $maxOrder = $section->directLessons()->max('sort_order') ?? 0;

        $section->directLessons()->create([
            ...$request->validated(),
            'section_id' => $section->id,
            'sub_section_id' => null,
            'status' => LessonStatus::Draft,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('teacher.courses.show', $course)
            ->with('success', 'تم إنشاء الدرس.');

    public function destroy(Course $course, Section $section, Lesson $lesson): RedirectResponse
    {
        $this->ensureCourseAccess($course);
    public function edit(int|string $courseId, int|string $sectionId, int|string $lessonId): View
    {
        Log::info('Teacher SectionLessonController@edit hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $lesson] = $this->resolveDirectSectionLesson($courseId, $sectionId, $lessonId);

        $this->ensureCourseAccess($course);
            ->route('teacher.courses.show', $course)
            ->with('success', "تم حذف الدرس «{$title}».");
    }

    public function move(Request $request, Course $course, Section $section, Lesson $lesson): RedirectResponse
    {
        $this->ensureCourseAccess($course);
        $this->ensureSectionInCourse($course, $section);
        $this->assertLessonBelongsToSection($lesson, $section);
        $this->authorize('update', $lesson);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($section->directLessons(), $lesson, $direction);

        return back()->with('success', $moved ? 'تم تحديث ترتيب الدرس.' : 'لا يمكن نقل الدرس أكثر.');
    }
}

