<?php

use App\Http\Controllers\Docs\DocsCourseController;
use App\Http\Controllers\Docs\DocsLessonController;
use App\Http\Controllers\Docs\DocsSearchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\CourseDocsController;
use App\Http\Controllers\ShareLinkController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseMemberController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\SectionController as AdminSectionController;
use App\Http\Controllers\Admin\SubSectionController as AdminSubSectionController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Auth\TeacherAuthController;
use App\Http\Controllers\Teacher\CourseController as TeacherCourseController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\LessonContentController;
use App\Http\Controllers\Teacher\LessonController;
use App\Http\Controllers\Teacher\SectionLessonController as TeacherSectionLessonController;
use App\Http\Controllers\Teacher\SectionController;
use App\Http\Controllers\Admin\SectionLessonController as AdminSectionLessonController;
use App\Http\Controllers\Teacher\SubSectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Public docs reader
Route::prefix('docs')->name('docs.')->group(function () {
    Route::get('/search', [DocsSearchController::class, 'index'])->name('search');

    Route::get('/{courseSlug}', [DocsCourseController::class, 'show'])->name('course');
    Route::get('/{courseSlug}/{lessonSlug}', [DocsLessonController::class, 'show'])->name('lesson');
});

Route::get('/share/{token}', [ShareLinkController::class, 'show'])->name('share.show');

// Docs preview (authenticated staff / course teachers)
Route::middleware('auth')->prefix('docs/preview')->name('docs.preview.')->group(function () {
    Route::get('/{courseSlug}', function (\Illuminate\Http\Request $request, string $courseSlug) {
        return app(DocsCourseController::class)->show($request, $courseSlug, true);
    })->name('course');

    Route::get('/{courseSlug}/{lessonSlug}', function (\Illuminate\Http\Request $request, string $courseSlug, string $lessonSlug) {
        return app(DocsLessonController::class)->show($request, $courseSlug, $lessonSlug, true);
    })->name('lesson');
});

// Teacher authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [TeacherAuthController::class, 'showLogin'])->name('teacher.login');
    Route::post('/login', [TeacherAuthController::class, 'login'])->name('teacher.login.submit');
});

Route::post('/logout', [TeacherAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('teacher.logout');

// Admin authentication
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    });

    Route::post('/logout', [AdminAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('teachers', TeacherController::class)->except(['show', 'destroy']);
        Route::patch('teachers/{teacher}/toggle', [TeacherController::class, 'toggleActive'])
            ->name('teachers.toggle');

        Route::resource('courses', AdminCourseController::class);
        Route::get('courses/{course}/docs-settings', [CourseDocsController::class, 'edit'])->name('courses.docs.edit');
        Route::put('courses/{course}/docs-settings', [CourseDocsController::class, 'update'])->name('courses.docs.update');
        Route::post('courses/{course}/share-links', [CourseDocsController::class, 'storeShareLink'])->name('courses.share-links.store');
        Route::delete('courses/{course}/share-links/{shareLink}', [CourseDocsController::class, 'destroyShareLink'])->name('courses.share-links.destroy');
        Route::get('courses/{course}/members', [CourseMemberController::class, 'edit'])
            ->name('courses.members.edit');
        Route::put('courses/{course}/members', [CourseMemberController::class, 'update'])
            ->name('courses.members.update');

        Route::prefix('courses/{course}')->name('courses.')->scopeBindings()->group(function () {
            Route::post('sections', [AdminSectionController::class, 'store'])->name('sections.store');
            Route::put('sections/{section}', [AdminSectionController::class, 'update'])->name('sections.update');
            Route::delete('sections/{section}', [AdminSectionController::class, 'destroy'])->name('sections.destroy');
            Route::patch('sections/{section}/move', [AdminSectionController::class, 'move'])->name('sections.move');

            Route::post('sections/{section}/lessons', [AdminSectionLessonController::class, 'store'])
                ->withoutScopedBindings()
                ->name('section-lessons.store');
            Route::get('sections/{section}/lessons/{lesson}', [AdminSectionLessonController::class, 'show'])->name('section-lessons.show');
            Route::delete('sections/{section}/lessons/{lesson}', [AdminSectionLessonController::class, 'destroy'])->name('section-lessons.destroy');
            Route::patch('sections/{section}/lessons/{lesson}/move', [AdminSectionLessonController::class, 'move'])->name('section-lessons.move');

            Route::post('sections/{section}/sub-sections', [AdminSubSectionController::class, 'store'])->name('sub-sections.store');
            Route::put('sections/{section}/sub-sections/{subSection}', [AdminSubSectionController::class, 'update'])->name('sub-sections.update');
            Route::delete('sections/{section}/sub-sections/{subSection}', [AdminSubSectionController::class, 'destroy'])->name('sub-sections.destroy');
            Route::patch('sections/{section}/sub-sections/{subSection}/move', [AdminSubSectionController::class, 'move'])->name('sub-sections.move');

            Route::post('sections/{section}/sub-sections/{subSection}/lessons', [AdminLessonController::class, 'store'])
                ->withoutScopedBindings()
                ->name('lessons.store');
            Route::get('sections/{section}/sub-sections/{subSection}/lessons/{lesson}', [AdminLessonController::class, 'show'])->name('lessons.show');
            Route::delete('sections/{section}/sub-sections/{subSection}/lessons/{lesson}', [AdminLessonController::class, 'destroy'])->name('lessons.destroy');
            Route::patch('sections/{section}/sub-sections/{subSection}/lessons/{lesson}/move', [AdminLessonController::class, 'move'])->name('lessons.move');
        });
    });
});

// Teacher area
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [TeacherDashboardController::class, 'index'])->name('dashboard');

    Route::get('courses', [TeacherCourseController::class, 'index'])->name('courses.index');

    Route::prefix('courses/{course}')->scopeBindings()->group(function () {
        Route::get('/', [TeacherCourseController::class, 'show'])->name('courses.show');

        Route::post('sections', [SectionController::class, 'store'])->name('sections.store');
        Route::put('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');
        Route::patch('sections/{section}/move', [SectionController::class, 'move'])->name('sections.move');

        Route::post('sections/{section}/lessons', [TeacherSectionLessonController::class, 'store'])
            ->withoutScopedBindings()
            ->name('section-lessons.store');
        Route::get('sections/{section}/lessons/{lesson}/edit', [TeacherSectionLessonController::class, 'edit'])->name('section-lessons.edit');
        Route::put('sections/{section}/lessons/{lesson}', [TeacherSectionLessonController::class, 'update'])->name('section-lessons.update');
        Route::delete('sections/{section}/lessons/{lesson}', [TeacherSectionLessonController::class, 'destroy'])->name('section-lessons.destroy');
        Route::patch('sections/{section}/lessons/{lesson}/status', [TeacherSectionLessonController::class, 'updateStatus'])->name('section-lessons.status');
        Route::patch('sections/{section}/lessons/{lesson}/move', [TeacherSectionLessonController::class, 'move'])->name('section-lessons.move');
        Route::post('sections/{section}/lessons/{lesson}/autosave', [LessonContentController::class, 'autosaveSection'])->name('section-lessons.autosave');
        Route::get('sections/{section}/lessons/{lesson}/versions/{version}', [LessonContentController::class, 'showVersionSection'])->name('section-lessons.versions.show');
        Route::post('sections/{section}/lessons/{lesson}/versions/{version}/restore', [LessonContentController::class, 'restoreSection'])->name('section-lessons.versions.restore');

        Route::post('sections/{section}/sub-sections', [SubSectionController::class, 'store'])->name('sub-sections.store');
        Route::put('sections/{section}/sub-sections/{subSection}', [SubSectionController::class, 'update'])->name('sub-sections.update');
        Route::delete('sections/{section}/sub-sections/{subSection}', [SubSectionController::class, 'destroy'])->name('sub-sections.destroy');
        Route::patch('sections/{section}/sub-sections/{subSection}/move', [SubSectionController::class, 'move'])->name('sub-sections.move');

        Route::post('sections/{section}/sub-sections/{subSection}/lessons', [LessonController::class, 'store'])
            ->withoutScopedBindings()
            ->name('lessons.store');
        Route::get('sections/{section}/sub-sections/{subSection}/lessons/{lesson}/edit', [LessonController::class, 'edit'])->name('lessons.edit');
        Route::put('sections/{section}/sub-sections/{subSection}/lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
        Route::delete('sections/{section}/sub-sections/{subSection}/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
        Route::patch('sections/{section}/sub-sections/{subSection}/lessons/{lesson}/status', [LessonController::class, 'updateStatus'])->name('lessons.status');
        Route::patch('sections/{section}/sub-sections/{subSection}/lessons/{lesson}/move', [LessonController::class, 'move'])->name('lessons.move');

        Route::post('sections/{section}/sub-sections/{subSection}/lessons/{lesson}/autosave', [LessonContentController::class, 'autosave'])->name('lessons.autosave');
        Route::get('sections/{section}/sub-sections/{subSection}/lessons/{lesson}/versions/{version}', [LessonContentController::class, 'showVersion'])->name('lessons.versions.show');
        Route::post('sections/{section}/sub-sections/{subSection}/lessons/{lesson}/versions/{version}/restore', [LessonContentController::class, 'restore'])->name('lessons.versions.restore');
    });
});
