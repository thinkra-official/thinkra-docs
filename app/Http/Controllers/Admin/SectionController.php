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

    public function store(Request $request, int|string $courseId): RedirectResponse
    {
        Log::info('Admin SectionController@store hit', ['courseId' => $courseId]);

        $course = Course::findOrFail($courseId);

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

    public function update(Request $request, int|string $courseId, int|string $sectionId): RedirectResponse
    {
        Log::info('Admin SectionController@update hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
        ]);

        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

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

    public function move(Request $request, int|string $courseId, int|string $sectionId): RedirectResponse
    {
        Log::info('Admin SectionController@move hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'direction' => $request->input('direction'),
        ]);

        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($course->sections(), $section, $direction);

        return redirect()
            ->route('admin.courses.show', $course->id)
            ->with('success', $moved ? 'تم تحديث ترتيب القسم.' : 'لا يمكن نقل القسم أكثر.');
    }
}
