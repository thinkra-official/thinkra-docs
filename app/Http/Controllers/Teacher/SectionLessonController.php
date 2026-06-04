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

use App\Services\CourseAccessService;

use App\Services\LessonContentService;

use App\Support\NestedCourseRoute;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;



class SectionLessonController extends Controller

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
    }



    public function edit(int|string $courseId, int|string $sectionId, int|string $lessonId): View
    {
        Log::info('Teacher SectionLessonController@edit hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $lesson] = $this->resolveDirectSectionLesson($courseId, $sectionId, $lessonId);

        $this->ensureCourseAccess($course);
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

            'canEdit' => $canEdit,

            'versions' => $versions,

            'changeLogs' => $changeLogs,

            'inSubSection' => false,

        ]);

    }



    public function update(UpdateLessonRequest $request, int|string $courseId, int|string $sectionId, int|string $lessonId): RedirectResponse
    {
        Log::info('Teacher SectionLessonController@update hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $lesson] = $this->resolveDirectSectionLesson($courseId, $sectionId, $lessonId);

        $this->ensureCourseAccess($course);
        $this->assertLessonBelongsToSection($lesson, $section);
        $this->authorize('update', $lesson);



        $this->content->save($lesson, auth()->user(), $request->validated(), forceVersion: true);



        return back()->with('success', 'تم حفظ محتوى الدرس.');

    }



    public function updateStatus(Request $request, int|string $courseId, int|string $sectionId, int|string $lessonId): RedirectResponse
    {
        Log::info('Teacher SectionLessonController@updateStatus hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $lesson] = $this->resolveDirectSectionLesson($courseId, $sectionId, $lessonId);

        $this->ensureCourseAccess($course);
        $this->assertLessonBelongsToSection($lesson, $section);
        $this->authorize('changeStatus', $lesson);



        $validated = $request->validate([

            'status' => ['required', 'in:DRAFT,NEEDS_REVIEW,READY,PUBLISHED'],

        ]);



        $lesson->update(['status' => $validated['status']]);



        return back()->with('success', 'تم تحديث حالة الدرس.');

    }



    public function destroy(int|string $courseId, int|string $sectionId, int|string $lessonId): RedirectResponse
    {
        Log::info('Teacher SectionLessonController@destroy hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $lesson] = $this->resolveDirectSectionLesson($courseId, $sectionId, $lessonId);

        $this->ensureCourseAccess($course);
        $this->assertLessonBelongsToSection($lesson, $section);
        $this->authorize('update', $lesson);

        $title = $lesson->title;
        $lesson->delete();

        return redirect()
            ->route('teacher.courses.show', $course->id)
            ->with('success', "تم حذف الدرس «{$title}».");
    }



    public function move(Request $request, int|string $courseId, int|string $sectionId, int|string $lessonId): RedirectResponse
    {
        Log::info('Teacher SectionLessonController@move hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'lessonId' => $lessonId,
            'direction' => $request->input('direction'),
        ]);

        [$course, $section, $lesson] = $this->resolveDirectSectionLesson($courseId, $sectionId, $lessonId);

        $this->ensureCourseAccess($course);
        $this->assertLessonBelongsToSection($lesson, $section);
        $this->authorize('update', $lesson);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($section->directLessons(), $lesson, $direction);

        return redirect()
            ->route('teacher.courses.show', $course->id)
            ->with('success', $moved ? 'تم تحديث ترتيب الدرس.' : 'لا يمكن نقل الدرس أكثر.');
    }

}


