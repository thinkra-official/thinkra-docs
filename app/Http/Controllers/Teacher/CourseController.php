<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseAccessService;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        protected CourseAccessService $access
    ) {}

    public function index(): View
    {
        $courses = $this->access->coursesForTeacher(auth()->user())->get();

        return view('teacher.courses.index', compact('courses'));
    }

    public function show(Course $course): View
    {
        if (! $this->access->canAccessCourse(auth()->user(), $course)) {
            abort(404);
        }

        $this->authorize('view', $course);

        $course->load(['sections.directLessons', 'sections.subSections.lessons']);
        $memberRole = $this->access->memberRole(auth()->user(), $course);

        return view('teacher.courses.show', compact('course', 'memberRole'));
    }
}
