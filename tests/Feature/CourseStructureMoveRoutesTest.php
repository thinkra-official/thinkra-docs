<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseStructureMoveRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function createStructure(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0512000001']);

        $course = Course::create(['title' => 'Move Test Course', 'slug' => 'move-test-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create(['title' => 'Chapter A', 'sort_order' => 1]);
        $section2 = $course->sections()->create(['title' => 'Chapter B', 'sort_order' => 2]);

        $subSection = $section->subSections()->create(['title' => 'Part A', 'sort_order' => 1]);
        $subSection2 = $section->subSections()->create(['title' => 'Part B', 'sort_order' => 2]);

        $directLesson = $section->directLessons()->create([
            'title' => 'Direct A',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $directLesson2 = $section->directLessons()->create([
            'title' => 'Direct B',
            'status' => LessonStatus::Draft,
            'sort_order' => 2,
        ]);

        $subLesson = $subSection->lessons()->create([
            'title' => 'Sub A',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $subLesson2 = $subSection->lessons()->create([
            'title' => 'Sub B',
            'status' => LessonStatus::Draft,
            'sort_order' => 2,
        ]);

        return compact(
            'teacher',
            'course',
            'section',
            'section2',
            'subSection',
            'subSection2',
            'directLesson',
            'directLesson2',
            'subLesson',
            'subLesson2'
        );
    }

    public function test_admin_section_move_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('admin.courses.sections.move', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section2']->id,
            ]), ['direction' => 'up'])
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertEquals(1, $data['section2']->fresh()->sort_order);
    }

    public function test_teacher_section_move_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->patch(route('teacher.sections.move', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section2']->id,
            ]), ['direction' => 'up'])
            ->assertRedirect(route('teacher.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertEquals(1, $data['section2']->fresh()->sort_order);
    }

    public function test_admin_sub_section_move_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('admin.courses.sub-sections.move', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection2']->id,
            ]), ['direction' => 'up'])
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertEquals(1, $data['subSection2']->fresh()->sort_order);
    }

    public function test_teacher_sub_section_move_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->patch(route('teacher.sub-sections.move', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection2']->id,
            ]), ['direction' => 'up'])
            ->assertRedirect(route('teacher.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertEquals(1, $data['subSection2']->fresh()->sort_order);
    }

    public function test_admin_direct_lesson_move_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('admin.courses.section-lessons.move', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'lessonId' => $data['directLesson2']->id,
            ]), ['direction' => 'up'])
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertEquals(1, $data['directLesson2']->fresh()->sort_order);
    }

    public function test_teacher_direct_lesson_move_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->patch(route('teacher.section-lessons.move', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'lessonId' => $data['directLesson2']->id,
            ]), ['direction' => 'up'])
            ->assertRedirect(route('teacher.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertEquals(1, $data['directLesson2']->fresh()->sort_order);
    }

    public function test_admin_sub_section_lesson_move_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('admin.courses.lessons.move', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
                'lessonId' => $data['subLesson2']->id,
            ]), ['direction' => 'up'])
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertEquals(1, $data['subLesson2']->fresh()->sort_order);
    }

    public function test_teacher_sub_section_lesson_move_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->patch(route('teacher.lessons.move', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
                'lessonId' => $data['subLesson2']->id,
            ]), ['direction' => 'up'])
            ->assertRedirect(route('teacher.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertEquals(1, $data['subLesson2']->fresh()->sort_order);
    }

    public function test_admin_direct_lesson_move_via_post_method_spoof(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $url = route('admin.courses.section-lessons.move', [
            'courseId' => $data['course']->id,
            'sectionId' => $data['section']->id,
            'lessonId' => $data['directLesson2']->id,
        ]);

        $this->actingAs($admin)
            ->post($url, ['_method' => 'PATCH', 'direction' => 'up'])
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
            ->assertSessionHas('success');
    }
}
