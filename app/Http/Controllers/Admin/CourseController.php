<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::withCount('members', 'sections')
            ->orderBy('title')
            ->paginate(15);

        return view('admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        $teachers = User::teachers()->active()->orderBy('name')->get();

        return view('admin.courses.create', compact('teachers'));
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = Course::create([
            'title' => $request->validated('title'),
            'slug' => Str::slug($request->validated('title')),
            'description' => $request->validated('description'),
        ]);

        $this->syncMembers($course, $request->validated('members', []));

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'تم إنشاء الكورس.');
    }

    public function show(Course $course): View
    {
        $course->load(['sections.directLessons', 'sections.subSections.lessons', 'members']);

        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course): View
    {
        $teachers = User::teachers()->active()->orderBy('name')->get();
        $assigned = $course->members->pluck('pivot.role', 'id');

        return view('admin.courses.edit', compact('course', 'teachers', 'assigned'));
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->safe()->only(['title', 'description']));

        if ($request->has('title')) {
            $course->update(['slug' => Str::slug($request->validated('title'))]);
        }

        if ($request->has('members')) {
            $this->syncMembers($course, $request->validated('members', []));
        }

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'تم تحديث الكورس.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'تم حذف الكورس.');
    }

    protected function syncMembers(Course $course, array $members): void
    {
        $sync = [];
        foreach ($members as $userId => $role) {
            if ($role) {
                $sync[$userId] = ['role' => $role];
            }
        }
        $course->members()->sync($sync);
    }
}
