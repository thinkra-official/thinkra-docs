<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LessonStatus;
use App\Http\Controllers\Concerns\ReordersCourseStructure;
use App\Http\Controllers\Concerns\ResolvesLessonPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Support\NestedCourseRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionLessonController extends Controller
{
    use ReordersCourseStructure;
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

    public function store(StoreLessonRequest $request, Course $course, Section $section): RedirectResponse
    {
        $this->ensureSectionInCourse($course, $section);

        $maxOrder = $section->directLessons()->max('sort_order') ?? 0;

        $lesson = $section->directLessons()->create([
            ...$request->validated(),
            'section_id' => $section->id,
            'sub_section_id' => null,
            'status' => LessonStatus::Draft,
            'sort_order' => $maxOrder + 1,
        ]);

        $lesson->refresh();

        $routeParams = NestedCourseRoute::sectionLesson($course, $section, $lesson);

        if (! $lesson->isDirectInSection()) {
            return redirect()
                ->route('admin.courses.show', $course)
                ->with('success', 'تم إنشاء الدرس.');
        }

        return redirect()
            ->route('admin.courses.section-lessons.show', $routeParams)
            ->with('success', 'تم إنشاء الدرس.');
    }

    public function destroy(Course $course, Section $section, Lesson $lesson): RedirectResponse
    {
        $this->ensureSectionInCourse($course, $section);
        $this->assertLessonBelongsToSection($lesson, $section);

        $title = $lesson->title;
        $lesson->delete();

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', "تم حذف الدرس «{$title}».");
    }

    public function move(Request $request, Course $course, Section $section, Lesson $lesson): RedirectResponse
    {
        $this->ensureSectionInCourse($course, $section);
        $this->assertLessonBelongsToSection($lesson, $section);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($section->directLessons(), $lesson, $direction);

        return back()->with('success', $moved ? 'تم تحديث ترتيب الدرس.' : 'لا يمكن نقل الدرس أكثر.');
    }
}
