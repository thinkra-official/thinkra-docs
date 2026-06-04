<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LessonStatus;
use App\Http\Controllers\Concerns\ReordersCourseStructure;
use App\Http\Controllers\Concerns\ResolvesCourseStructureFromRouteIds;
use App\Http\Controllers\Concerns\ResolvesLessonPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SectionLessonController extends Controller
{
    use ReordersCourseStructure;
    use ResolvesCourseStructureFromRouteIds;
    use ResolvesLessonPlacement;

    public function show(Course $course, Section $section, Lesson $lesson): View
    {
        $this->ensureSectionInCourse($course, $section);
        $this->assertLessonBelongsToSection($lesson, $section);

        return view('admin.lessons.show', [
            'course' => $course,
            'section' => $section,
            'subSection' => null,
            'lesson' => $lesson,
        ]);
    }

    public function store(StoreLessonRequest $request, int|string $courseId, int|string $sectionId): RedirectResponse
    {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        $maxOrder = $section->directLessons()->max('sort_order') ?? 0;

        $section->directLessons()->create([
            ...$request->validated(),
            'section_id' => $section->id,
            'sub_section_id' => null,
            'status' => LessonStatus::Draft,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'تم إنشاء الدرس.');
    }

    public function destroy(int|string $courseId, int|string $sectionId, int|string $lessonId): RedirectResponse
    {
        Log::info('Admin SectionLessonController@destroy hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $lesson] = $this->resolveDirectSectionLesson($courseId, $sectionId, $lessonId);
        $this->assertLessonBelongsToSection($lesson, $section);

        $title = $lesson->title;
        $lesson->delete();

        return redirect()
            ->route('admin.courses.show', $course->id)
            ->with('success', "تم حذف الدرس «{$title}».");
    }

    public function move(Request $request, int|string $courseId, int|string $sectionId, int|string $lessonId): RedirectResponse
    {
        Log::info('Admin SectionLessonController@move hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'lessonId' => $lessonId,
            'direction' => $request->input('direction'),
        ]);

        [$course, $section, $lesson] = $this->resolveDirectSectionLesson($courseId, $sectionId, $lessonId);
        $this->assertLessonBelongsToSection($lesson, $section);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($section->directLessons(), $lesson, $direction);

        return redirect()
            ->route('admin.courses.show', $course->id)
            ->with('success', $moved ? 'تم تحديث ترتيب الدرس.' : 'لا يمكن نقل الدرس أكثر.');
    }
}
