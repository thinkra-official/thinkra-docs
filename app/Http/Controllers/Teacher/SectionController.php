<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\ReordersCourseStructure;
use App\Http\Controllers\Concerns\ResolvesCourseStructureFromRouteIds;
use App\Http\Controllers\Concerns\ResolvesSectionDeleteCounts;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Services\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
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

    public function store(Request $request, int|string $courseId): RedirectResponse
    {
        Log::info('Teacher SectionController@store hit', ['courseId' => $courseId]);

        $course = Course::findOrFail($courseId);

        $this->ensureCourseAccess($course);
        $this->authorize('manage', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $maxOrder = $course->sections()->max('sort_order') ?? 0;

        $course->sections()->create([
            'title' => $validated['title'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'تم إضافة الفصل.');
    }

    public function update(Request $request, int|string $courseId, int|string $sectionId): RedirectResponse
    {
        Log::info('Teacher SectionController@update hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
        ]);

        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        $this->ensureCourseAccess($course);
        $this->authorize('manage', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $section->update($validated);

        return back()->with('success', 'تم تحديث الفصل.');
    }

    public function destroy(int|string $courseId, int|string $sectionId): RedirectResponse
    {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        $this->ensureCourseAccess($course);
        $this->authorize('manage', $course);

        Log::info('Teacher SectionController@destroy hit', [
            'courseId' => $course->id,
            'sectionId' => $section->id,
        ]);

        $summary = $this->sectionDeleteSummary($section);
        $section->delete();

        $message = 'تم حذف الفصل';
        if ($summary['subSections'] > 0 || $summary['lessons'] > 0) {
            $message .= sprintf(
                ' مع %d قسم و %d درس',
                $summary['subSections'],
                $summary['lessons']
            );
        }
        $message .= '.';

        return redirect()
            ->route('teacher.courses.show', $course)
            ->with('success', $message);
    }

    public function move(Request $request, int|string $courseId, int|string $sectionId): RedirectResponse
    {
        Log::info('Teacher SectionController@move hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'direction' => $request->input('direction'),
        ]);

        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        $this->ensureCourseAccess($course);
        $this->authorize('manage', $course);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($course->sections(), $section, $direction);

        return redirect()
            ->route('teacher.courses.show', $course->id)
            ->with('success', $moved ? 'تم تحديث ترتيب الفصل.' : 'لا يمكن نقل الفصل أكثر.');
    }
}
