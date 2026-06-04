<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use App\Services\CourseAccessService;

class CoursePolicy
{
    public function __construct(
        protected CourseAccessService $access
    ) {}

    public function view(User $user, Course $course): bool
    {
        return $this->access->canViewContent($user, $course);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->access->canEditContent($user, $course);
    }

    public function manage(User $user, Course $course): bool
    {
        return $this->access->canManageCourse($user, $course);
    }
}
