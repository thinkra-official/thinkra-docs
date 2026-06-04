<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\LessonStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\SubSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NestedRouteBindingTest extends TestCase
{
    use RefreshDatabase;

    protected function createCourseStructure(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0503333333']);

        $course = Course::create([
            'title' => 'Binding Course',
            'slug' => 'binding-course',
        ]);

        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create([
            'title' => 'Section A',
            'sort_order' => 1,
        ]);

        $subSection = $section->subSections()->create([
            'title' => 'Sub A',
            'sort_order' => 1,
        ]);

        $lesson = $subSection->lessons()->create([
            'title' => 'Lesson A',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        return compact('teacher', 'course', 'section', 'subSection', 'lesson');
    }

    public function test_section_move_uses_scoped_binding(): void
    {
        ['teacher' => $teacher, 'course' => $course, 'section' => $section] = $this->createCourseStructure();

        $section2 = $course->sections()->create(['title' => 'Section B', 'sort_order' => 2]);

        $this->actingAs($teacher)
            ->patch(route('teacher.sections.move', [$course, $section2]), ['direction' => 'up'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(1, $section2->fresh()->sort_order);
        $this->assertEquals(2, $section->fresh()->sort_order);
    }

    public function test_sub_section_move_uses_scoped_binding(): void
    {
        ['teacher' => $teacher, 'course' => $course, 'section' => $section, 'subSection' => $subSection] = $this->createCourseStructure();

        $subSection2 = $section->subSections()->create(['title' => 'Sub B', 'sort_order' => 2]);

        $this->actingAs($teacher)
            ->patch(route('teacher.sub-sections.move', [$course, $section, $subSection2]), ['direction' => 'up'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(1, $subSection2->fresh()->sort_order);
    }

    public function test_lesson_move_uses_scoped_binding(): void
    {
        $data = $this->createCourseStructure();

        $lesson2 = $data['subSection']->lessons()->create([
            'title' => 'Lesson B',
            'status' => LessonStatus::Draft,
            'sort_order' => 2,
        ]);

        $this->actingAs($data['teacher'])
            ->patch(route('teacher.lessons.move', [
                $data['course'],
                $data['section'],
                $data['subSection'],
                $lesson2,
            ]), ['direction' => 'up'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(1, $lesson2->fresh()->sort_order);
    }

    public function test_admin_section_move_accepts_has_many_scope(): void
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);
        ['course' => $course, 'section' => $section] = $this->createCourseStructure();

        $section2 = $course->sections()->create(['title' => 'Section B', 'sort_order' => 2]);

        $this->actingAs($admin)
            ->patch(route('admin.courses.sections.move', [$course, $section2]), ['direction' => 'up'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_lesson_edit_uses_scoped_binding(): void
    {
        $data = $this->createCourseStructure();

        $this->actingAs($data['teacher'])
            ->get(route('teacher.lessons.edit', [
                $data['course'],
                $data['section'],
                $data['subSection'],
                $data['lesson'],
            ]))
            ->assertOk();
    }

    public function test_lesson_autosave_uses_scoped_binding(): void
    {
        $data = $this->createCourseStructure();

        $this->actingAs($data['teacher'])
            ->postJson(route('teacher.lessons.autosave', [
                $data['course'],
                $data['section'],
                $data['subSection'],
                $data['lesson'],
            ]), [
                'title' => 'Lesson A updated',
                'objective' => 'Goal',
                'main_content' => '<p>Body</p>',
                'teacher_notes' => null,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_section_lesson_edit_uses_scoped_binding(): void
    {
        $data = $this->createCourseStructure();

        $directLesson = $data['section']->directLessons()->create([
            'title' => 'Direct Lesson',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $this->actingAs($data['teacher'])
            ->get(route('teacher.section-lessons.edit', [
                $data['course'],
                $data['section'],
                $directLesson,
            ]))
            ->assertOk();
    }

    public function test_section_lesson_move_uses_scoped_binding(): void
    {
        $data = $this->createCourseStructure();

        $lesson1 = $data['section']->directLessons()->create([
            'title' => 'Direct A',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $lesson2 = $data['section']->directLessons()->create([
            'title' => 'Direct B',
            'status' => LessonStatus::Draft,
            'sort_order' => 2,
        ]);

        $this->actingAs($data['teacher'])
            ->patch(route('teacher.section-lessons.move', [
                $data['course'],
                $data['section'],
                $lesson2,
            ]), ['direction' => 'up'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(1, $lesson2->fresh()->sort_order);
    }

    public function test_lesson_xor_placement_rejects_both_parents(): void
    {
        $data = $this->createCourseStructure();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        Lesson::create([
            'section_id' => $data['section']->id,
            'sub_section_id' => $data['subSection']->id,
            'title' => 'Invalid',
            'status' => LessonStatus::Draft,
            'sort_order' => 99,
        ]);
    }

    public function test_section_from_wrong_course_returns_not_found(): void
    {
        ['teacher' => $teacher, 'course' => $course, 'section' => $section] = $this->createCourseStructure();

        $otherCourse = Course::create([
            'title' => 'Other',
            'slug' => 'other',
        ]);

        $otherCourse->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $this->actingAs($teacher)
            ->patch(route('teacher.sections.move', [$otherCourse, $section]), ['direction' => 'up'])
            ->assertNotFound();
    }
}
