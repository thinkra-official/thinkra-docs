<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseMemberController extends Controller
{
    public function edit(Course $course): View
    {
        $teachers = User::teachers()->active()->orderBy('name')->get();
        $assigned = $course->members->pluck('pivot.role', 'id');

        return view('admin.courses.members', compact('course', 'teachers', 'assigned'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'members' => ['nullable', 'array'],
            'members.*' => ['nullable', 'in:OWNER,EDITOR,VIEWER'],
        ]);

        $sync = [];
        foreach ($validated['members'] ?? [] as $userId => $role) {
            if ($role) {
                $sync[$userId] = ['role' => $role];
            }
        }

        $course->members()->sync($sync);

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'تم تحديث أعضاء الكورس.');
    }
}
