<?php



namespace App\Http\Controllers\Teacher;



use App\Enums\LessonStatus;

use App\Http\Controllers\Concerns\ReordersCourseStructure;
use App\Http\Controllers\Concerns\ResolvesCourseStructureFromRouteIds;
use App\Http\Controllers\Concerns\ResolvesLessonPlacement;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreLessonRequest;

use App\Http\Requests\Teacher\UpdateLessonRequest;

use App\Models\Course;

use App\Models\Lesson;

use App\Models\Section;

use App\Models\SubSection;

use App\Services\CourseAccessService;

use App\Services\LessonContentService;

use App\Support\NestedCourseRoute;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;



class LessonController extends Controller

{

    use ReordersCourseStructure;
    use ResolvesCourseStructureFromRouteIds;
    use ResolvesLessonPlacement;



    public function __construct(

        protected CourseAccessService $access,

        protected LessonContentService $content

    ) {}



    protected function ensureCourseAccess(Course $course): void

    {

        if (! $this->access->canAccessCourse(auth()->user(), $course)) {

            abort(404);

        }

    }



    public function store(
        StoreLessonRequest $request,
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId
    ): RedirectResponse {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);
        $subSection = $this->resolveSubSectionInSection($section, $subSectionId);

        $this->ensureCourseAccess($course);
        $this->authorize('update', $course);

        $maxOrder = $subSection->lessons()->max('sort_order') ?? 0;

        $subSection->lessons()->create([
            ...$request->validated(),
            'section_id' => null,
            'sub_section_id' => $subSection->id,
            'status' => LessonStatus::Draft,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('teacher.courses.show', $course)
            ->with('success', 'تم إنشاء الدرس.');
    }



    public function edit(Course $course, Section $section, SubSection $subSection, Lesson $lesson): View

    {

        $this->ensureCourseAccess($course);

        $this->ensureSectionInCourse($course, $section);

        $this->ensureSubSectionInSection($section, $subSection);

        $this->assertLessonBelongsToSubSection($lesson, $subSection);



        $this->authorize('view', $lesson);



        $canEdit = auth()->user()->can('update', $lesson);



        $versions = $lesson->versions()->with('author:id,name')->limit(30)->get();

        $changeLogs = $lesson->changeLogs()->with('user:id,name')->limit(20)->get();



        return view('teacher.lessons.edit', [

            'course' => $course,

            'section' => $section,

            'subSection' => $subSection,

            'lesson' => $lesson,

            'canEdit' => $canEdit,

            'versions' => $versions,

            'changeLogs' => $changeLogs,

            'inSubSection' => true,

        ]);

    }



    public function update(UpdateLessonRequest $request, Course $course, Section $section, SubSection $subSection, Lesson $lesson): RedirectResponse

    {

        $this->ensureCourseAccess($course);

        $this->ensureSectionInCourse($course, $section);

        $this->ensureSubSectionInSection($section, $subSection);

        $this->assertLessonBelongsToSubSection($lesson, $subSection);

        $this->authorize('update', $lesson);



        $this->content->save($lesson, auth()->user(), $request->validated(), forceVersion: true);



        return back()->with('success', 'تم حفظ محتوى الدرس.');

    }



    public function updateStatus(Request $request, Course $course, Section $section, SubSection $subSection, Lesson $lesson): RedirectResponse

    {

        $this->ensureCourseAccess($course);

        $this->ensureSectionInCourse($course, $section);

        $this->ensureSubSectionInSection($section, $subSection);

        $this->assertLessonBelongsToSubSection($lesson, $subSection);

        $this->authorize('changeStatus', $lesson);



        $validated = $request->validate([

            'status' => ['required', 'in:DRAFT,NEEDS_REVIEW,READY,PUBLISHED'],

        ]);



        $lesson->update(['status' => $validated['status']]);



        return back()->with('success', 'تم تحديث حالة الدرس.');

    }



    public function destroy(
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId,
        int|string $lessonId
    ): RedirectResponse {
        [$course, $section, $subSection, $lesson] = $this->resolveSubSectionLesson(
            $courseId,
            $sectionId,
            $subSectionId,
            $lessonId
        );

        $this->ensureCourseAccess($course);
        $this->assertLessonBelongsToSubSection($lesson, $subSection);
        $this->authorize('update', $lesson);

        $title = $lesson->title;
        $lesson->delete();

        return redirect()
            ->route('teacher.courses.show', $course)
            ->with('success', "تم حذف الدرس «{$title}».");
    }


    public function move(Request $request, Course $course, Section $section, SubSection $subSection, Lesson $lesson): RedirectResponse

    {

        $this->ensureCourseAccess($course);

        $this->ensureSectionInCourse($course, $section);

        $this->ensureSubSectionInSection($section, $subSection);

        $this->assertLessonBelongsToSubSection($lesson, $subSection);

        $this->authorize('update', $lesson);



        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];



        $moved = $this->moveSortOrder($subSection->lessons(), $lesson, $direction);



        return back()->with('success', $moved ? 'تم تحديث ترتيب الدرس.' : 'لا يمكن نقل الدرس أكثر.');

    }

}


