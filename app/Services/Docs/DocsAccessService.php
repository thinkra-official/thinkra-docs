<?php

namespace App\Services\Docs;

use App\Enums\CourseVisibility;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseShareLink;
use App\Models\Lesson;
use App\Models\User;
use App\Services\CourseAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocsAccessService
{
    public const SHARE_SESSION_PREFIX = 'docs_share_';

    public function __construct(
        protected CourseAccessService $courseAccess
    ) {}

    public function shareSessionKey(Course $course): string
    {
        return self::SHARE_SESSION_PREFIX.$course->id;
    }

    public function grantShareAccess(Course $course, string $token): void
    {
        session([$this->shareSessionKey($course) => $token]);
    }

    public function hasShareAccess(Course $course, Request $request): bool
    {
        $stored = $request->session()->get($this->shareSessionKey($course));

        if (! is_string($stored) || $stored === '') {
            return false;
        }

        $link = CourseShareLink::query()
            ->where('course_id', $course->id)
            ->where('token', $stored)
            ->first();

        return $link !== null && $link->isValid();
    }

    public function isStaff(?User $user): bool
    {
        return $user !== null && $user->is_active && $user->role->isAdmin();
    }

    public function isCourseTeacher(?User $user, Course $course): bool
    {
        if ($user === null || ! $user->is_active) {
            return false;
        }

        if ($this->isStaff($user)) {
            return true;
        }

        return $this->courseAccess->canAccessCourse($user, $course);
    }

    public function canViewCourse(Course $course, ?User $user, Request $request, bool $preview = false): bool
    {
        if ($preview) {
            return $this->isCourseTeacher($user, $course);
        }

        if ($course->require_login && $user === null) {
            return false;
        }

        if ($this->isCourseTeacher($user, $course)) {
            return true;
        }

        if ($this->hasShareAccess($course, $request)) {
            return true;
        }

        return match ($course->visibility) {
            CourseVisibility::Public => true,
            CourseVisibility::Unlisted => true,
            CourseVisibility::Private => false,
        };
    }

    public function canViewLesson(Lesson $lesson, ?User $user, Request $request, bool $preview = false): bool
    {
        $course = $lesson->resolveCourse();

        if (! $this->canViewCourse($course, $user, $request, $preview)) {
            return false;
        }

        if ($preview) {
            return $this->isCourseTeacher($user, $course);
        }

        return $lesson->isPublished();
    }

    public function canShowTeacherNotes(?User $user, Course $course, bool $preview): bool
    {
        return $preview && $this->isCourseTeacher($user, $course);
    }

    public function assertCanViewCourse(Course $course, ?User $user, Request $request, bool $preview = false): void
    {
        if (! $this->canViewCourse($course, $user, $request, $preview)) {
            if ($course->require_login && $user === null) {
                abort(redirect()->guest(route('teacher.login'))->getTargetUrl() ? 302 : 403);
            }

            throw new NotFoundHttpException;
        }
    }

    public function assertCanViewLesson(Lesson $lesson, ?User $user, Request $request, bool $preview = false): void
    {
        if (! $this->canViewLesson($lesson, $user, $request, $preview)) {
            throw new NotFoundHttpException;
        }
    }

    public function loginRedirectUrl(Course $course): string
    {
        return route('teacher.login', ['redirect' => request()->fullUrl()]);
    }
}
