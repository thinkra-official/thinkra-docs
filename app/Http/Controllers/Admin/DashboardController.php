<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'teachersCount' => User::teachers()->count(),
            'coursesCount' => Course::count(),
            'activeTeachers' => User::teachers()->active()->count(),
        ]);
    }
}
