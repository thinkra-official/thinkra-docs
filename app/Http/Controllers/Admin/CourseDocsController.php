<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCourseDocsSettingsRequest;
use App\Models\Course;
use App\Models\CourseShareLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CourseDocsController extends Controller
{
    public function edit(Course $course): View
    {
        $course->load(['shareLinks' => fn ($q) => $q->latest()]);

        return view('admin.courses.docs-settings', compact('course'));
    }

    public function update(UpdateCourseDocsSettingsRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return redirect()
            ->route('admin.courses.docs.edit', $course)
            ->with('success', 'تم حفظ إعدادات النشر.');
    }

    public function storeShareLink(Request $request, int|string $courseId): RedirectResponse
    {
        $course = Course::findOrFail($courseId);

        $request->validate([
            'expires' => ['required', 'in:1,7,30,never'],
        ]);

        $expires = match ($request->input('expires')) {
            '1' => now()->addDay(),
            '7' => now()->addDays(7),
            '30' => now()->addDays(30),
            default => null,
        };

        $link = $course->shareLinks()->create([
            'expires_at' => $expires,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.courses.docs.edit', $course)
            ->with('success', 'تم إنشاء رابط المشاركة.')
            ->with('share_url', route('share.show', $link->token));
    }

    public function destroyShareLink(int|string $courseId, int|string $shareLinkId): RedirectResponse
    {
        Log::info('Admin CourseShareLinkController@destroy hit', [
            'courseId' => $courseId,
            'shareLinkId' => $shareLinkId,
        ]);

        $course = Course::findOrFail($courseId);

        $shareLink = CourseShareLink::query()
            ->whereKey($shareLinkId)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $shareLink->delete();

        return redirect()
            ->route('admin.courses.docs.edit', $course)
            ->with('success', 'تم حذف رابط المشاركة.');
    }
}
