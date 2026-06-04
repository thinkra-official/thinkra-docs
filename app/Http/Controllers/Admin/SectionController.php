<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersCourseStructure;
use App\Http\Controllers\Concerns\ResolvesCourseStructureFromRouteIds;
use App\Http\Controllers\Concerns\ResolvesSectionDeleteCounts;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
{
    use ReordersCourseStructure;
    use ResolvesCourseStructureFromRouteIds;
    use ResolvesSectionDeleteCounts;

    public function store(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $maxOrder = $course->sections()->max('sort_order') ?? 0;

        $course->sections()->create([
            'title' => $validated['title'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'تم إضافة القسم.');
    }

    public function update(Request $request, Course $course, Section $section): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $section->update($validated);

        return back()->with('success', 'تم تحديث القسم.');
    }

    public function destroy(int|string $courseId, int|string $sectionId): RedirectResponse
    {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        Log::info('Admin SectionController@destroy hit', [
            'courseId' => $course->id,
            'sectionId' => $section->id,
        ]);

        $summary = $this->sectionDeleteSummary($section);
        $section->delete();

        $message = 'تم حذف القسم';
        if ($summary['subSections'] > 0 || $summary['lessons'] > 0) {
            $message .= sprintf(
                ' مع %d صب قسم و %d درس',
                $summary['subSections'],
                $summary['lessons']
            );
        }
        $message .= '.';

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', $message);
    }

    public function move(Request $request, Course $course, Section $section): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($course->sections(), $section, $direction);

        return back()->with('success', $moved ? 'تم تحديث ترتيب القسم.' : 'لا يمكن نقل القسم أكثر.');
    }
}
