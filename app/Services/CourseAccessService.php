<?php

namespace App\Services;

use App\Enums\CourseMemberRole;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;

class CourseAccessService
{
    public function canAccessCourse(User $user, Course $course): bool
    {
        if ($user->role?->isAdmin()) {
            return true;
        }

        if ($user->role !== UserRole::Teacher || ! $user->is_active) {
            return false;
        }

        return $course->members()->where('users.id', $user->id)->exists();
    }

    public function memberRole(User $user, Course $course): ?CourseMemberRole
    {
        if ($user->role?->isAdmin()) {
            return CourseMemberRole::Owner;
        }

        return $course->memberRoleFor($user);
    }

    public function canViewContent(User $user, Course $course): bool
    {
        $role = $this->memberRole($user, $course);

        return $role !== null;
    }

    public function canEditContent(User $user, Course $course): bool
    {
        $role = $this->memberRole($user, $course);

        return $role?->canEditContent() ?? false;
    }

    public function canManageCourse(User $user, Course $course): bool
    {
        if ($user->role?->isAdmin()) {
            return true;
        }

        $role = $this->memberRole($user, $course);

        return $role?->canManageCourse() ?? false;
    }

    /** @return \Illuminate\Database\Eloquent\Builder<\App\Models\Course> */
    public function coursesForTeacher(User $user)
    {
        return Course::query()
            ->whereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->with(['sections.directLessons', 'sections.subSections.lessons'])
            ->orderBy('title');
    }
}
