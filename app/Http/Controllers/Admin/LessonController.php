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

use App\Models\SubSection;

use App\Support\NestedCourseRoute;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\View\View;



class LessonController extends Controller

{

    use ReordersCourseStructure;

    use ResolvesLessonPlacement;



    public function show(Course $course, Section $section, SubSection $subSection, Lesson $lesson): View

    {

        $this->ensureSectionInCourse($course, $section);

        $this->ensureSubSectionInSection($section, $subSection);

        $this->assertLessonBelongsToSubSection($lesson, $subSection);



        return view('admin.lessons.show', compact('course', 'section', 'subSection', 'lesson'));

    }



    public function store(StoreLessonRequest $request, Course $course, Section $section, SubSection $subSection): RedirectResponse

    {

        $this->ensureSectionInCourse($course, $section);

        $this->ensureSubSectionInSection($section, $subSection);



        $maxOrder = $subSection->lessons()->max('sort_order') ?? 0;



        $lesson = $subSection->lessons()->create([

            ...$request->validated(),

            'status' => LessonStatus::Draft,

            'sort_order' => $maxOrder + 1,

        ]);



        return redirect()

            ->route('admin.courses.lessons.show', NestedCourseRoute::subSectionLesson($course, $section, $subSection, $lesson))

            ->with('success', 'تم إنشاء الدرس.');

    }



    public function destroy(Course $course, Section $section, SubSection $subSection, Lesson $lesson): RedirectResponse

    {

        $this->ensureSectionInCourse($course, $section);

        $this->ensureSubSectionInSection($section, $subSection);

        $this->assertLessonBelongsToSubSection($lesson, $subSection);



        $title = $lesson->title;

        $lesson->delete();



        return redirect()

            ->route('admin.courses.show', $course)

            ->with('success', "تم حذف الدرس «{$title}».");

    }



    public function move(Request $request, Course $course, Section $section, SubSection $subSection, Lesson $lesson): RedirectResponse

    {

        $this->ensureSectionInCourse($course, $section);

        $this->ensureSubSectionInSection($section, $subSection);

        $this->assertLessonBelongsToSubSection($lesson, $subSection);



        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];



        $moved = $this->moveSortOrder($subSection->lessons(), $lesson, $direction);



        return back()->with('success', $moved ? 'تم تحديث ترتيب الدرس.' : 'لا يمكن نقل الدرس أكثر.');

    }

}


