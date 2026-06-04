<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubSectionStoreRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function createStructure(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0508000001']);

        $course = Course::create(['title' => 'Sub Section Store Course', 'slug' => 'sub-section-store-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create(['title' => 'Chapter', 'sort_order' => 1]);

        return compact('teacher', 'course', 'section');
    }

    public function test_admin_sub_section_store_creates_sub_section_without_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.courses.sub-sections.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]), ['title' => 'Admin Sub Part'])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $subSection = $data['section']->subSections()->where('title', 'Admin Sub Part')->first();
        $this->assertNotNull($subSection);
        $this->assertSame($data['section']->id, $subSection->section_id);
    }

    public function test_teacher_sub_section_store_creates_sub_section_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->post(route('teacher.sub-sections.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]), ['title' => 'Teacher Sub Part'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(
            $data['section']->subSections()->where('title', 'Teacher Sub Part')->first()
        );
    }

    public function test_admin_sub_section_store_wrong_section_returns_404(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $otherCourse = Course::create(['title' => 'Other', 'slug' => 'other-sub-store']);

        $this->actingAs($admin)
            ->post(route('admin.courses.sub-sections.store', [
                'courseId' => $otherCourse->id,
                'sectionId' => $data['section']->id,
            ]), ['title' => 'Should Fail'])
            ->assertNotFound();
    }
}
