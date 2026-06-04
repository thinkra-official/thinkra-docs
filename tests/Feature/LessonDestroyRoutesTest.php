<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonDestroyRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function createStructure(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0509000001']);

        $course = Course::create(['title' => 'Destroy Test Course', 'slug' => 'destroy-test-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create(['title' => 'Chapter', 'sort_order' => 1]);
        $subSection = $section->subSections()->create(['title' => 'Part', 'sort_order' => 1]);

        $directLesson = $section->directLessons()->create([
            'title' => 'Direct To Delete',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $subLesson = $subSection->lessons()->create([
            'title' => 'Sub To Delete',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        return compact('teacher', 'course', 'section', 'subSection', 'directLesson', 'subLesson');
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
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
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
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
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
            ->assertRedirect(route('teacher.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lessons', ['id' => $data['subLesson']->id]);
    }

    public function test_admin_direct_lesson_destroy_via_post_method_spoof(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $url = route('admin.courses.section-lessons.destroy', [
            'courseId' => $data['course']->id,
            'sectionId' => $data['section']->id,
            'lessonId' => $data['directLesson']->id,
        ]);

        $this->actingAs($admin)
            ->post($url, ['_method' => 'DELETE'])
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lessons', ['id' => $data['directLesson']->id]);
    }

    public function test_admin_sub_section_lesson_destroy_via_post_method_spoof(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $url = route('admin.courses.lessons.destroy', [
            'courseId' => $data['course']->id,
            'sectionId' => $data['section']->id,
            'subSectionId' => $data['subSection']->id,
            'lessonId' => $data['subLesson']->id,
        ]);

        $this->actingAs($admin)
            ->post($url, ['_method' => 'DELETE'])
            ->assertRedirect(route('admin.courses.show', $data['course']->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('lessons', ['id' => $data['subLesson']->id]);
    }

    public function test_admin_course_show_includes_lesson_delete_action_urls(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $directUrl = route('admin.courses.section-lessons.destroy', [
            'courseId' => $data['course']->id,
            'sectionId' => $data['section']->id,
            'lessonId' => $data['directLesson']->id,
        ]);

        $subUrl = route('admin.courses.lessons.destroy', [
            'courseId' => $data['course']->id,
            'sectionId' => $data['section']->id,
            'subSectionId' => $data['subSection']->id,
            'lessonId' => $data['subLesson']->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.courses.show', $data['course']))
            ->assertOk()
            ->assertSee('lesson delete action: '.$directUrl, false)
            ->assertSee('lesson delete action: '.$subUrl, false)
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_admin_sub_section_lesson_destroy_wrong_sub_section_returns_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $otherSub = $data['section']->subSections()->create(['title' => 'Other', 'sort_order' => 2]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.lessons.destroy', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $otherSub->id,
                'lessonId' => $data['subLesson']->id,
            ]))
            ->assertNotFound();
    }
}
