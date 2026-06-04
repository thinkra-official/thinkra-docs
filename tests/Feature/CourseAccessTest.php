<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_access_unassigned_course(): void
    {
        $teacher = User::factory()->teacher()->create([
            'phone' => '0501111111',
        ]);

        $course = Course::create([
            'title' => 'Private Course',
            'slug' => 'private-course',
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.courses.show', $course))
            ->assertNotFound();
    }

    public function test_teacher_can_access_assigned_course(): void
    {
        $teacher = User::factory()->teacher()->create([
            'phone' => '0502222222',
        ]);

        $course = Course::create([
            'title' => 'My Course',
            'slug' => 'my-course',
        ]);

        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Editor->value]);

        $this->actingAs($teacher)
            ->get(route('teacher.courses.show', $course))
            ->assertOk();
    }

    public function test_viewer_can_view_lesson_but_not_update(): void
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0504444444']);
        $course = Course::create(['title' => 'View Course', 'slug' => 'view-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Viewer->value]);

        $section = $course->sections()->create(['title' => 'S', 'sort_order' => 1]);
        $sub = $section->subSections()->create(['title' => 'SS', 'sort_order' => 1]);
        $lesson = $sub->lessons()->create([
            'title' => 'L',
            'status' => \App\Enums\LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.lessons.edit', [
                'course' => $course,
                'section' => $section,
                'subSection' => $sub,
                'lesson' => $lesson,
            ]))
            ->assertOk()
            ->assertSee('لديك صلاحية مشاهدة فقط', false);

        $this->actingAs($teacher)
            ->put(route('teacher.lessons.update', [
                'course' => $course,
                'section' => $section,
                'subSection' => $sub,
                'lesson' => $lesson,
            ]), [
                'title' => 'Hack',
                'objective' => null,
                'main_content' => null,
                'teacher_notes' => null,
            ])
            ->assertForbidden();
    }
}
