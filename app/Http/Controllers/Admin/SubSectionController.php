<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersCourseStructure;
use App\Http\Controllers\Concerns\ResolvesCourseStructureFromRouteIds;
use App\Http\Controllers\Concerns\ResolvesSectionDeleteCounts;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\SubSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubSectionController extends Controller
{
    use ReordersCourseStructure;
    use ResolvesCourseStructureFromRouteIds;
    use ResolvesSectionDeleteCounts;

    public function store(Request $request, int|string $courseId, int|string $sectionId): RedirectResponse
    {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);

        Log::info('Admin SubSectionController@store hit', [
            'courseId' => $course->id,
            'sectionId' => $section->id,
            'title' => $request->input('title'),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $maxOrder = $section->subSections()->max('sort_order') ?? 0;

        $section->subSections()->create([
            'title' => $validated['title'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'تم إضافة الصب قسم.');
    }

    public function update(Request $request, int|string $courseId, int|string $sectionId, int|string $subSectionId): RedirectResponse
    {
        Log::info('Admin SubSectionController@update hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'subSectionId' => $subSectionId,
        ]);

        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);
        $subSection = $this->resolveSubSectionInSection($section, $subSectionId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $subSection->update($validated);

        return back()->with('success', 'تم تحديث الصب قسم.');
    }

    public function destroy(int|string $courseId, int|string $sectionId, int|string $subSectionId): RedirectResponse
    {
        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);
        $subSection = $this->resolveSubSectionInSection($section, $subSectionId);

        Log::info('Admin SubSectionController@destroy hit', [
            'courseId' => $course->id,
            'sectionId' => $section->id,
            'subSectionId' => $subSection->id,
        ]);

        $this->subSectionDeleteSummary($subSection);
        $subSection->delete();

        return redirect()
            ->route('admin.courses.show', $course->id)
            ->with('success', 'تم حذف القسم الفرعي بنجاح');
    }

    public function move(Request $request, int|string $courseId, int|string $sectionId, int|string $subSectionId): RedirectResponse
    {
        Log::info('Admin SubSectionController@move hit', [
            'courseId' => $courseId,
            'sectionId' => $sectionId,
            'subSectionId' => $subSectionId,
            'direction' => $request->input('direction'),
        ]);

        [$course, $section] = $this->resolveCourseAndSection($courseId, $sectionId);
        $subSection = $this->resolveSubSectionInSection($section, $subSectionId);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($section->subSections(), $subSection, $direction);

        return redirect()
            ->route('admin.courses.show', $course->id)
            ->with('success', $moved ? 'تم تحديث ترتيب الصب قسم.' : 'لا يمكن نقل الصب قسم أكثر.');
    }
}
