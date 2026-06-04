<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\CourseAccessService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CourseAccessService $access
    ) {}

    public function index(): View
    {
        $courses = $this->access->coursesForTeacher(auth()->user())->get();

        return view('teacher.dashboard', compact('courses'));
    }
}
