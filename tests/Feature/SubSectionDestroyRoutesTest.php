<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubSectionDestroyRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function createStructure(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0510000001']);

        $course = Course::create(['title' => 'Sub Destroy Course', 'slug' => 'sub-destroy-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create(['title' => 'Chapter', 'sort_order' => 1]);
        $subSection = $section->subSections()->create(['title' => 'Part To Delete', 'sort_order' => 1]);

        return compact('teacher', 'course', 'section', 'subSection');
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

    public function test_admin_sub_section_destroy_wrong_section_returns_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $otherCourse = Course::create(['title' => 'Other', 'slug' => 'other-sub-destroy']);
        $otherSection = $otherCourse->sections()->create(['title' => 'Other Section', 'sort_order' => 1]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.sub-sections.destroy', [
                'courseId' => $otherCourse->id,
                'sectionId' => $otherSection->id,
                'subSectionId' => $data['subSection']->id,
            ]))
            ->assertNotFound();
    }
}
