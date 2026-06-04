<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);

        Paginator::useTailwind();

        Route::bind('teacher', function (string $value) {
            return User::query()
                ->where('id', $value)
                ->where('role', UserRole::Teacher)
                ->firstOrFail();
        });
    }
}
