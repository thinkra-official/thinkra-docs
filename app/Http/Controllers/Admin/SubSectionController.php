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

    public function update(Request $request, Course $course, Section $section, SubSection $subSection): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);
        abort_unless($subSection->section_id === $section->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $subSection->update($validated);

        return back()->with('success', 'تم تحديث الصب قسم.');
    }

    public function destroy(Course $course, Section $section, SubSection $subSection): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);
        abort_unless($subSection->section_id === $section->id, 404);

        $lessonsCount = $this->subSectionDeleteSummary($subSection);
        $subSection->delete();

        $message = 'تم حذف الصب قسم';
        if ($lessonsCount > 0) {
            $message .= " مع {$lessonsCount} درس";
        }
        $message .= '.';

        return back()->with('success', $message);
    }

    public function move(Request $request, Course $course, Section $section, SubSection $subSection): RedirectResponse
    {
        abort_unless($section->course_id === $course->id, 404);
        abort_unless($subSection->section_id === $section->id, 404);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $moved = $this->moveSortOrder($section->subSections(), $subSection, $direction);

        return back()->with('success', $moved ? 'تم تحديث ترتيب الصب قسم.' : 'لا يمكن نقل الصب قسم أكثر.');
    }
}
