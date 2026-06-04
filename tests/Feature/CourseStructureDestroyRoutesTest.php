<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseStructureDestroyRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function createStructure(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0511000001']);

        $course = Course::create(['title' => 'Destroy Audit Course', 'slug' => 'destroy-audit-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create(['title' => 'Chapter', 'sort_order' => 1]);
        $subSection = $section->subSections()->create(['title' => 'Part', 'sort_order' => 1]);

        $directLesson = $section->directLessons()->create([
            'title' => 'Direct Lesson',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $subLesson = $subSection->lessons()->create([
            'title' => 'Sub Lesson',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        return compact('teacher', 'course', 'section', 'subSection', 'directLesson', 'subLesson');
    }

    public function test_admin_section_destroy_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.sections.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]))
            ->assertRedirect(route('admin.courses.show', $data['course']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sections', ['id' => $data['section']->id]);
    }

    public function test_teacher_section_destroy_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->delete(route('teacher.sections.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]))
            ->assertRedirect(route('teacher.courses.show', $data['course']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sections', ['id' => $data['section']->id]);
    }

    public function test_admin_sub_section_destroy_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.sub-sections.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
            ]))
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
            ->assertSessionHas('success', 'تم حذف القسم الفرعي بنجاح');

        $this->assertDatabaseMissing('sub_sections', ['id' => $data['subSection']->id]);
    }

    public function test_teacher_sub_section_destroy_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->delete(route('teacher.sub-sections.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
            ]))
            ->assertRedirect(route('teacher.courses.show', $data['course']->id))
            ->assertSessionHas('success', 'تم حذف القسم الفرعي بنجاح');

        $this->assertDatabaseMissing('sub_sections', ['id' => $data['subSection']->id]);
    }

    public function test_admin_direct_lesson_destroy_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.section-lessons.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'lessonId' => $data['directLesson']->id,
            ]))
            ->assertRedirect(route('admin.courses.show', $data['course']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lessons', ['id' => $data['directLesson']->id]);
    }

    public function test_teacher_direct_lesson_destroy_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->delete(route('teacher.section-lessons.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'lessonId' => $data['directLesson']->id,
            ]))
            ->assertRedirect(route('teacher.courses.show', $data['course']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lessons', ['id' => $data['directLesson']->id]);
    }

    public function test_admin_sub_section_lesson_destroy_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.lessons.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
                'lessonId' => $data['subLesson']->id,
            ]))
            ->assertRedirect(route('admin.courses.show', $data['course']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lessons', ['id' => $data['subLesson']->id]);
    }

    public function test_teacher_sub_section_lesson_destroy_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->delete(route('teacher.lessons.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
                'lessonId' => $data['subLesson']->id,
            ]))
            ->assertRedirect(route('teacher.courses.show', $data['course']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lessons', ['id' => $data['subLesson']->id]);
    }
}
