<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\CourseAccessService;

class LessonPolicy
{
    public function __construct(
        protected CourseAccessService $access
    ) {}

    protected function courseFor(Lesson $lesson): Course
    {
        return $lesson->resolveCourse();
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $this->access->canViewContent($user, $this->courseFor($lesson));
    }

    public function create(User $user, Lesson $lesson): bool
    {
        return $this->access->canEditContent($user, $this->courseFor($lesson));
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $this->access->canEditContent($user, $this->courseFor($lesson));
    }

    public function changeStatus(User $user, Lesson $lesson): bool
    {
        return $this->access->canEditContent($user, $this->courseFor($lesson));
    }
}
