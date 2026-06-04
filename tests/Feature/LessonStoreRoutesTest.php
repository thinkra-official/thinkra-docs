<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonStoreRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function createStructure(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0507000001']);

        $course = Course::create(['title' => 'Store Test Course', 'slug' => 'store-test-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create(['title' => 'Chapter', 'sort_order' => 1]);
        $subSection = $section->subSections()->create(['title' => 'Part', 'sort_order' => 1]);

        return compact('teacher', 'course', 'section', 'subSection');
    }

    public function test_admin_direct_lesson_store_creates_lesson_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.courses.section-lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]), [
                'title' => 'Admin Direct 45',
                'objective' => '',
                'main_content' => '',
                'teacher_notes' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $lesson = $data['section']->directLessons()->where('title', 'Admin Direct 45')->first();
        $this->assertNotNull($lesson);
        $this->assertSame($data['section']->id, $lesson->section_id);
        $this->assertNull($lesson->sub_section_id);
    }

    public function test_teacher_direct_lesson_store_creates_lesson_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->post(route('teacher.section-lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]), ['title' => 'Teacher Direct Lesson'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(
            $data['section']->directLessons()->where('title', 'Teacher Direct Lesson')->first()
        );
    }

    public function test_admin_sub_section_lesson_store_creates_lesson_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.courses.lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
            ]), ['title' => 'Admin Sub Lesson'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $lesson = $data['subSection']->lessons()->where('title', 'Admin Sub Lesson')->first();
        $this->assertNotNull($lesson);
        $this->assertSame($data['subSection']->id, $lesson->sub_section_id);
        $this->assertNull($lesson->section_id);
    }

    public function test_teacher_sub_section_lesson_store_creates_lesson_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->post(route('teacher.lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
            ]), ['title' => 'Teacher Sub Lesson'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(
            $data['subSection']->lessons()->where('title', 'Teacher Sub Lesson')->first()
        );
    }

    public function test_admin_direct_lesson_store_wrong_section_returns_404(): void
    {
        $data = $this->createStructure();
        $otherCourse = Course::create(['title' => 'Other', 'slug' => 'other-course']);
        $otherSection = $otherCourse->sections()->create(['title' => 'Other S', 'sort_order' => 1]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.courses.section-lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $otherSection->id,
            ]), ['title' => 'Should Fail'])
            ->assertNotFound();

        $this->assertSame(0, $data['section']->directLessons()->count());
    }
}
