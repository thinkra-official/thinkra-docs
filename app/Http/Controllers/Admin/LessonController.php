<?php



namespace App\Http\Controllers\Admin;



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

use App\Services\LessonContentService;
use App\Support\NestedCourseRoute;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;



class LessonController extends Controller

{

    use ReordersCourseStructure;
    use ResolvesCourseStructureFromRouteIds;
    use ResolvesLessonPlacement;

    public function __construct(
        protected LessonContentService $content
    ) {}

    public function show(
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId,
        int|string $lessonId
    ): RedirectResponse {
        Log::info('Admin LessonController@show hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'subSectionId' => $subSectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $subSection, $lesson] = $this->resolveSubSectionLesson(
            $courseId,
            $sectionId,
            $subSectionId,
            $lessonId
        );

        return redirect()->route('admin.courses.lessons.edit', NestedCourseRoute::subSectionLesson(
            $course,
            $section,
            $subSection,
            $lesson
        ));
    }

    public function edit(
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId,
        int|string $lessonId
    ): View {
        Log::info('Admin LessonController@edit hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'subSectionId' => $subSectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $subSection, $lesson] = $this->resolveSubSectionLesson(
            $courseId,
            $sectionId,
            $subSectionId,
            $lessonId
        );

        $this->assertLessonBelongsToSubSection($lesson, $subSection);

        return view('admin.lessons.edit', $this->lessonEditorData($course, $section, $subSection, $lesson));
    }

    public function update(
        UpdateLessonRequest $request,
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId,
        int|string $lessonId
    ): RedirectResponse {
        Log::info('Admin LessonController@update hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'subSectionId' => $subSectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $subSection, $lesson] = $this->resolveSubSectionLesson(
            $courseId,
            $sectionId,
            $subSectionId,
            $lessonId
        );

        $this->assertLessonBelongsToSubSection($lesson, $subSection);

        $this->content->save($lesson, auth()->user(), $request->validated(), forceVersion: true);

        return back()->with('success', 'تم حفظ محتوى الدرس.');
    }

    public function updateStatus(
        Request $request,
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId,
        int|string $lessonId
    ): RedirectResponse {
        Log::info('Admin LessonController@updateStatus hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'subSectionId' => $subSectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $subSection, $lesson] = $this->resolveSubSectionLesson(
            $courseId,
            $sectionId,
            $subSectionId,
            $lessonId
        );

        $this->assertLessonBelongsToSubSection($lesson, $subSection);

        $validated = $request->validate([
            'status' => ['required', 'in:DRAFT,NEEDS_REVIEW,READY,PUBLISHED'],
        ]);

        $lesson->update(['status' => $validated['status']]);

        return back()->with('success', 'تم تحديث حالة الدرس.');
    }

    /** @return array<string, mixed> */
    protected function lessonEditorData(Course $course, Section $section, SubSection $subSection, Lesson $lesson): array
    {
        return [
            'course' => $course,
            'section' => $section,
            'subSection' => $subSection,
            'lesson' => $lesson,
            'canEdit' => true,
            'versions' => $lesson->versions()->with('author:id,name')->limit(30)->get(),
            'changeLogs' => $lesson->changeLogs()->with('user:id,name')->limit(20)->get(),
            'inSubSection' => true,
        ];
    }



    public function store(
        StoreLessonRequest $request,
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId
    ): RedirectResponse {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);
        $subSection = $this->resolveSubSectionInSection($section, $subSectionId);

        $maxOrder = $subSection->lessons()->max('sort_order') ?? 0;

        $subSection->lessons()->create([
            ...$request->validated(),
            'section_id' => null,
            'sub_section_id' => $subSection->id,
            'status' => LessonStatus::Draft,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'تم إنشاء الدرس.');
    }



    public function destroy(
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId,
        int|string $lessonId
    ): RedirectResponse {
        Log::info('Admin LessonController@destroy hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'subSectionId' => $subSectionId,
            'lessonId' => $lessonId,
        ]);

        [$course, $section, $subSection, $lesson] = $this->resolveSubSectionLesson(
            $courseId,
            $sectionId,
            $subSectionId,
            $lessonId
        );

        $this->assertLessonBelongsToSubSection($lesson, $subSection);

        $title = $lesson->title;
        $lesson->delete();

        return redirect()
            ->route('admin.courses.show', $course->id)
            ->with('success', "تم حذف الدرس «{$title}».");
    }


    public function move(
        Request $request,
        int|string $courseId,
        int|string $sectionId,
        int|string $subSectionId,
        int|string $lessonId
    ): RedirectResponse {
        Log::info('Admin LessonController@move hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'subSectionId' => $subSectionId,
            'lessonId' => $lessonId,
            'direction' => $request->input('direction'),
        ]);

        [$course, $section, $subSection, $lesson] = $this->resolveSubSectionLesson(
            $courseId,
            $sectionId,
            $subSectionId,
            $lessonId
        );

        $this->assertLessonBelongsToSubSection($lesson, $subSection);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($subSection->lessons(), $lesson, $direction);

        return redirect()
            ->route('admin.courses.show', $course->id)
            ->with('success', $moved ? 'تم تحديث ترتيب الدرس.' : 'لا يمكن نقل الدرس أكثر.');
    }

}


