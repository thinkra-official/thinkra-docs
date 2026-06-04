<?php

use App\Http\Controllers\Docs\DocsCourseController;
use App\Http\Controllers\Docs\DocsLessonController;
use App\Http\Controllers\Docs\DocsLessonProgressController;
use App\Http\Controllers\Docs\DocsSearchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\CourseDocsController;
use App\Http\Controllers\ShareLinkController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseStructureReorderController as AdminCourseStructureReorderController;
use App\Http\Controllers\Admin\CourseMemberController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LessonContentController as AdminLessonContentController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\SectionController as AdminSectionController;
use App\Http\Controllers\Admin\SubSectionController as AdminSubSectionController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Auth\TeacherAuthController;
use App\Http\Controllers\Teacher\CourseController as TeacherCourseController;
use App\Http\Controllers\Teacher\CourseStructureReorderController as TeacherCourseStructureReorderController;
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
    Route::post('/{courseSlug}/{lessonSlug}/complete', [DocsLessonProgressController::class, 'complete'])
        ->middleware('auth')
        ->name('lesson.complete');
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

        Route::patch('courses/{courseId}/structure/reorder', AdminCourseStructureReorderController::class)
            ->name('courses.structure.reorder');
        Route::post('courses/{courseId}/sections', [AdminSectionController::class, 'store'])
            ->name('courses.sections.store');
        Route::put('courses/{courseId}/sections/{sectionId}', [AdminSectionController::class, 'update'])
            ->name('courses.sections.update');
        Route::delete('courses/{courseId}/sections/{sectionId}', [AdminSectionController::class, 'destroy'])
            ->name('courses.sections.destroy');
        Route::patch('courses/{courseId}/sections/{sectionId}/move', [AdminSectionController::class, 'move'])
            ->name('courses.sections.move');
        Route::post('courses/{courseId}/sections/{sectionId}/lessons', [AdminSectionLessonController::class, 'store'])
            ->name('courses.section-lessons.store');
        Route::get('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}', [AdminSectionLessonController::class, 'show'])
            ->name('courses.section-lessons.show');
        Route::get('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/edit', [AdminSectionLessonController::class, 'edit'])
            ->name('courses.section-lessons.edit');
        Route::put('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}', [AdminSectionLessonController::class, 'update'])
            ->name('courses.section-lessons.update');
        Route::patch('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/status', [AdminSectionLessonController::class, 'updateStatus'])
            ->name('courses.section-lessons.status');
        Route::post('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/autosave', [AdminLessonContentController::class, 'autosaveSection'])
            ->name('courses.section-lessons.autosave');
        Route::get('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/versions/{versionId}', [AdminLessonContentController::class, 'showVersionSection'])
            ->name('courses.section-lessons.versions.show');
        Route::post('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/versions/{versionId}/restore', [AdminLessonContentController::class, 'restoreSection'])
            ->name('courses.section-lessons.versions.restore');
        Route::delete('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}', [AdminSectionLessonController::class, 'destroy'])
            ->name('courses.section-lessons.destroy');
        Route::patch('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/move', [AdminSectionLessonController::class, 'move'])
            ->name('courses.section-lessons.move');
        Route::post('courses/{courseId}/sections/{sectionId}/sub-sections', [AdminSubSectionController::class, 'store'])
            ->name('courses.sub-sections.store');
        Route::put('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}', [AdminSubSectionController::class, 'update'])
            ->name('courses.sub-sections.update');
        Route::delete('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}', [AdminSubSectionController::class, 'destroy'])
            ->name('courses.sub-sections.destroy');
        Route::patch('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/move', [AdminSubSectionController::class, 'move'])
            ->name('courses.sub-sections.move');
        Route::post('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons', [AdminLessonController::class, 'store'])
            ->name('courses.lessons.store');
        Route::get('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}', [AdminLessonController::class, 'show'])
            ->name('courses.lessons.show');
        Route::get('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/edit', [AdminLessonController::class, 'edit'])
            ->name('courses.lessons.edit');
        Route::put('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}', [AdminLessonController::class, 'update'])
            ->name('courses.lessons.update');
        Route::patch('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/status', [AdminLessonController::class, 'updateStatus'])
            ->name('courses.lessons.status');
        Route::post('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/autosave', [AdminLessonContentController::class, 'autosave'])
            ->name('courses.lessons.autosave');
        Route::get('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/versions/{versionId}', [AdminLessonContentController::class, 'showVersion'])
            ->name('courses.lessons.versions.show');
        Route::post('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/versions/{versionId}/restore', [AdminLessonContentController::class, 'restore'])
            ->name('courses.lessons.versions.restore');
        Route::delete('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}', [AdminLessonController::class, 'destroy'])
            ->name('courses.lessons.destroy');
        Route::patch('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/move', [AdminLessonController::class, 'move'])
            ->name('courses.lessons.move');
    });
});

// Teacher area
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [TeacherDashboardController::class, 'index'])->name('dashboard');

    Route::get('courses', [TeacherCourseController::class, 'index'])->name('courses.index');

    Route::get('courses/{courseId}', [TeacherCourseController::class, 'show'])->name('courses.show');
    Route::patch('courses/{courseId}/structure/reorder', TeacherCourseStructureReorderController::class)
        ->name('courses.structure.reorder');
    Route::post('courses/{courseId}/sections', [SectionController::class, 'store'])
        ->name('sections.store');
    Route::put('courses/{courseId}/sections/{sectionId}', [SectionController::class, 'update'])
        ->name('sections.update');
    Route::delete('courses/{courseId}/sections/{sectionId}', [SectionController::class, 'destroy'])
        ->name('sections.destroy');
    Route::patch('courses/{courseId}/sections/{sectionId}/move', [SectionController::class, 'move'])
        ->name('sections.move');
    Route::post('courses/{courseId}/sections/{sectionId}/lessons', [TeacherSectionLessonController::class, 'store'])
        ->name('section-lessons.store');
    Route::get('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/edit', [TeacherSectionLessonController::class, 'edit'])
        ->name('section-lessons.edit');
    Route::put('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}', [TeacherSectionLessonController::class, 'update'])
        ->name('section-lessons.update');
    Route::patch('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/status', [TeacherSectionLessonController::class, 'updateStatus'])
        ->name('section-lessons.status');
    Route::post('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/autosave', [LessonContentController::class, 'autosaveSection'])
        ->name('section-lessons.autosave');
    Route::get('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/versions/{versionId}', [LessonContentController::class, 'showVersionSection'])
        ->name('section-lessons.versions.show');
    Route::post('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/versions/{versionId}/restore', [LessonContentController::class, 'restoreSection'])
        ->name('section-lessons.versions.restore');
    Route::delete('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}', [TeacherSectionLessonController::class, 'destroy'])
        ->name('section-lessons.destroy');
    Route::patch('courses/{courseId}/sections/{sectionId}/lessons/{lessonId}/move', [TeacherSectionLessonController::class, 'move'])
        ->name('section-lessons.move');
    Route::post('courses/{courseId}/sections/{sectionId}/sub-sections', [SubSectionController::class, 'store'])
        ->name('sub-sections.store');
    Route::put('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}', [SubSectionController::class, 'update'])
        ->name('sub-sections.update');
    Route::delete('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}', [SubSectionController::class, 'destroy'])
        ->name('sub-sections.destroy');
    Route::patch('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/move', [SubSectionController::class, 'move'])
        ->name('sub-sections.move');
    Route::post('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons', [LessonController::class, 'store'])
        ->name('lessons.store');
    Route::get('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/edit', [LessonController::class, 'edit'])
        ->name('lessons.edit');
    Route::put('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}', [LessonController::class, 'update'])
        ->name('lessons.update');
    Route::patch('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/status', [LessonController::class, 'updateStatus'])
        ->name('lessons.status');
    Route::post('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/autosave', [LessonContentController::class, 'autosave'])
        ->name('lessons.autosave');
    Route::get('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/versions/{versionId}', [LessonContentController::class, 'showVersion'])
        ->name('lessons.versions.show');
    Route::post('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/versions/{versionId}/restore', [LessonContentController::class, 'restore'])
        ->name('lessons.versions.restore');
    Route::delete('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}', [LessonController::class, 'destroy'])
        ->name('lessons.destroy');
    Route::patch('courses/{courseId}/sections/{sectionId}/sub-sections/{subSectionId}/lessons/{lessonId}/move', [LessonController::class, 'move'])
        ->name('lessons.move');
});
