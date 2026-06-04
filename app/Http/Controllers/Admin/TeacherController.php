<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\UpdateTeacherRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        $teachers = User::teachers()
            ->with('courses')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        $courses = Course::orderBy('title')->get();

        return view('admin.teachers.create', compact('courses'));
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $teacher = User::create([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'),
            'role' => UserRole::Teacher,
            'is_active' => true,
        ]);

        if ($request->filled('course_ids')) {
            $sync = [];
            foreach ($request->validated('course_ids') as $courseId) {
                $role = $request->input("course_roles.{$courseId}", 'EDITOR');
                $sync[$courseId] = ['role' => $role];
            }
            $teacher->courses()->sync($sync);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'تم إضافة الأستاذ بنجاح.');
    }

    public function edit(User $teacher): View
    {
        abort_unless($teacher->role === UserRole::Teacher, 404);

        $courses = Course::orderBy('title')->get();
        $assigned = $teacher->courses->pluck('pivot.role', 'id');

        return view('admin.teachers.edit', compact('teacher', 'courses', 'assigned'));
    }

    public function update(UpdateTeacherRequest $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === UserRole::Teacher, 404);

        $data = $request->safe()->only(['name', 'phone', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $teacher->update($data);

        if ($request->has('course_ids')) {
            $sync = [];
            foreach ($request->validated('course_ids', []) as $courseId) {
                $role = $request->input("course_roles.{$courseId}", 'EDITOR');
                $sync[$courseId] = ['role' => $role];
            }
            $teacher->courses()->sync($sync);
        }

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'تم تحديث بيانات الأستاذ.');
    }

    public function toggleActive(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === UserRole::Teacher, 404);

        $teacher->update(['is_active' => ! $teacher->is_active]);

        return back()->with('success', $teacher->is_active ? 'تم تفعيل الأستاذ.' : 'تم تعطيل الأستاذ.');
    }
}
