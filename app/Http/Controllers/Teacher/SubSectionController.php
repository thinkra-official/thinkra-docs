<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\ReordersCourseStructure;
use App\Http\Controllers\Concerns\ResolvesCourseStructureFromRouteIds;
use App\Http\Controllers\Concerns\ResolvesSectionDeleteCounts;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\SubSection;
use App\Services\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubSectionController extends Controller
{
    use ReordersCourseStructure;
    use ResolvesCourseStructureFromRouteIds;
    use ResolvesSectionDeleteCounts;
    public function __construct(
        protected CourseAccessService $access
    ) {}

    protected function ensureCourseAccess(Course $course): void
    {
        if (! $this->access->canAccessCourse(auth()->user(), $course)) {
            abort(404);
        }
    }

    public function store(Request $request, int|string $courseId, int|string $sectionId): RedirectResponse
    {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        $this->ensureCourseAccess($course);
        $this->authorize('manage', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $maxOrder = $section->subSections()->max('sort_order') ?? 0;

        $section->subSections()->create([
            'title' => $validated['title'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'تم إضافة القسم.');
    }

    public function update(Request $request, Course $course, Section $section, SubSection $subSection): RedirectResponse
    {
        $this->ensureCourseAccess($course);
        abort_unless($section->course_id === $course->id, 404);
        abort_unless($subSection->section_id === $section->id, 404);
        $this->authorize('manage', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $subSection->update($validated);

        return back()->with('success', 'تم تحديث القسم.');
    }

    public function destroy(int|string $courseId, int|string $sectionId, int|string $subSectionId): RedirectResponse
    {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);
        $subSection = $this->resolveSubSectionInSection($section, $subSectionId);

        $this->ensureCourseAccess($course);
        $this->authorize('manage', $course);

        Log::info('Teacher SubSectionController@destroy hit', [
            'courseId' => $course->id,
            'sectionId' => $section->id,
            'subSectionId' => $subSection->id,
        ]);

        $this->subSectionDeleteSummary($subSection);
        $subSection->delete();

        return redirect()
            ->route('teacher.courses.show', $course->id)
            ->with('success', 'تم حذف القسم الفرعي بنجاح');
    }

    public function move(Request $request, Course $course, Section $section, SubSection $subSection): RedirectResponse
    {
        $this->ensureCourseAccess($course);
        abort_unless($section->course_id === $course->id, 404);
        abort_unless($subSection->section_id === $section->id, 404);
        $this->authorize('manage', $course);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($section->subSections(), $subSection, $direction);

        return back()->with('success', $moved ? 'تم تحديث ترتيب الصب قسم.' : 'لا يمكن نقل الصب قسم أكثر.');
    }
}
