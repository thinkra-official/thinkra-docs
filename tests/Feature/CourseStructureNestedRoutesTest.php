<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\LessonVersion;
use App\Models\User;
use App\Support\NestedCourseRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseStructureNestedRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function structure(): array
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $teacher = User::factory()->teacher()->create(['phone' => '0504444444']);

        $course = Course::create(['title' => 'Nested Course', 'slug' => 'nested-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create(['title' => 'Section', 'sort_order' => 1]);
        $subSection = $section->subSections()->create(['title' => 'Sub', 'sort_order' => 1]);

        $directLesson = $section->directLessons()->create([
            'title' => 'Direct',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
            'section_id' => $section->id,
            'sub_section_id' => null,
        ]);

        $subLesson = $subSection->lessons()->create([
            'title' => 'Sub Lesson',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
            'section_id' => null,
            'sub_section_id' => $subSection->id,
        ]);

        $directVersion = LessonVersion::create([
            'lesson_id' => $directLesson->id,
            'created_by' => $teacher->id,
            'created_at' => now(),
            'title' => 'Direct v1',
            'objective' => null,
            'main_content' => '<p>d</p>',
            'teacher_notes' => null,
        ]);

        $subVersion = LessonVersion::create([
            'lesson_id' => $subLesson->id,
            'created_by' => $teacher->id,
            'created_at' => now(),
            'title' => 'Sub v1',
            'objective' => null,
            'main_content' => '<p>s</p>',
            'teacher_notes' => null,
        ]);

        return compact(
            'admin',
            'teacher',
            'course',
            'section',
            'subSection',
            'directLesson',
            'subLesson',
            'directVersion',
            'subVersion'
        );
    }

    public function test_admin_direct_lesson_show_redirects_to_edit(): void
    {
        $d = $this->structure();

        $this->actingAs($d['admin'])
            ->get(route('admin.courses.section-lessons.show', NestedCourseRoute::sectionLesson(
                $d['course'],
                $d['section'],
                $d['directLesson']
            )))
            ->assertRedirect(route('admin.courses.section-lessons.edit', NestedCourseRoute::sectionLesson(
                $d['course'],
                $d['section'],
                $d['directLesson']
            )));
    }

    public function test_admin_sub_section_lesson_edit_with_editor(): void
    {
        $d = $this->structure();

        $this->actingAs($d['admin'])
            ->get(route('admin.courses.lessons.edit', NestedCourseRoute::subSectionLesson(
                $d['course'],
                $d['section'],
                $d['subSection'],
                $d['subLesson']
            )))
            ->assertOk()
            ->assertSee($d['subLesson']->title)
            ->assertSee('main_content', false)
            ->assertSee('حفظ الآن');
    }

    public function test_admin_direct_lesson_edit_update_autosave(): void
    {
        $d = $this->structure();
        $params = NestedCourseRoute::sectionLesson($d['course'], $d['section'], $d['directLesson']);

        $this->actingAs($d['admin'])
            ->get(route('admin.courses.section-lessons.edit', $params))
            ->assertOk()
            ->assertSee('id="lesson-form"', false);

        $this->actingAs($d['admin'])
            ->put(route('admin.courses.section-lessons.update', $params), [
                'title' => 'Direct Admin',
                'objective' => 'هدف',
                'main_content' => '<p>محتوى</p>',
                'teacher_notes' => null,
            ])
            ->assertRedirect();

        $this->actingAs($d['admin'])
            ->postJson(route('admin.courses.section-lessons.autosave', $params), [
                'title' => 'Direct Admin',
                'objective' => 'هدف',
                'main_content' => '<p>autosave</p>',
                'teacher_notes' => null,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_teacher_direct_lesson_edit_update_status_autosave_version(): void
    {
        $d = $this->structure();
        $params = NestedCourseRoute::sectionLesson($d['course'], $d['section'], $d['directLesson']);

        $this->actingAs($d['teacher'])
            ->get(route('teacher.section-lessons.edit', $params))
            ->assertOk();

        $this->actingAs($d['teacher'])
            ->put(route('teacher.section-lessons.update', $params), [
                'title' => 'Direct Updated',
                'objective' => 'obj',
                'main_content' => '<p>x</p>',
                'teacher_notes' => null,
            ])
            ->assertRedirect();

        $this->actingAs($d['teacher'])
            ->patch(route('teacher.section-lessons.status', $params), ['status' => 'READY'])
            ->assertRedirect();

        $this->actingAs($d['teacher'])
            ->postJson(route('teacher.section-lessons.autosave', $params), [
                'title' => 'Direct Updated',
                'objective' => 'obj',
                'main_content' => '<p>autosave</p>',
                'teacher_notes' => null,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $versionParams = NestedCourseRoute::sectionVersion(
            $d['course'],
            $d['section'],
            $d['directLesson'],
            $d['directVersion']
        );

        $this->actingAs($d['teacher'])
            ->getJson(route('teacher.section-lessons.versions.show', $versionParams))
            ->assertOk()
            ->assertJsonPath('title', 'Direct v1');

        $this->actingAs($d['teacher'])
            ->postJson(route('teacher.section-lessons.versions.restore', $versionParams))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_teacher_sub_section_lesson_edit_update_autosave_version(): void
    {
        $d = $this->structure();
        $params = NestedCourseRoute::subSectionLesson(
            $d['course'],
            $d['section'],
            $d['subSection'],
            $d['subLesson']
        );

        $this->actingAs($d['teacher'])
            ->get(route('teacher.lessons.edit', $params))
            ->assertOk();

        $this->actingAs($d['teacher'])
            ->put(route('teacher.lessons.update', $params), [
                'title' => 'Sub Updated',
                'objective' => null,
                'main_content' => '<p>sub</p>',
                'teacher_notes' => null,
            ])
            ->assertRedirect();

        $this->actingAs($d['teacher'])
            ->patch(route('teacher.lessons.status', $params), ['status' => 'PUBLISHED'])
            ->assertRedirect();

        $this->actingAs($d['teacher'])
            ->postJson(route('teacher.lessons.autosave', $params), [
                'title' => 'Sub Updated',
                'objective' => null,
                'main_content' => '<p>auto</p>',
                'teacher_notes' => null,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $versionParams = NestedCourseRoute::subSectionVersion(
            $d['course'],
            $d['section'],
            $d['subSection'],
            $d['subLesson'],
            $d['subVersion']
        );

        $this->actingAs($d['teacher'])
            ->getJson(route('teacher.lessons.versions.show', $versionParams))
            ->assertOk();

        $this->actingAs($d['teacher'])
            ->postJson(route('teacher.lessons.versions.restore', $versionParams))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_teacher_course_show_uses_course_id(): void
    {
        $d = $this->structure();

        $this->actingAs($d['teacher'])
            ->get(route('teacher.courses.show', ['courseId' => $d['course']->id]))
            ->assertOk()
            ->assertSee($d['course']->title);
    }

    public function test_admin_section_and_sub_section_update_with_raw_ids(): void
    {
        $d = $this->structure();

        $this->actingAs($d['admin'])
            ->from(route('admin.courses.show', $d['course']))
            ->put(route('admin.courses.sections.update', NestedCourseRoute::section($d['course'], $d['section'])), [
                'title' => 'Section Renamed',
            ])
            ->assertRedirect(route('admin.courses.show', $d['course']));

        $this->assertEquals('Section Renamed', $d['section']->fresh()->title);

        $this->actingAs($d['admin'])
            ->put(route('admin.courses.sub-sections.update', NestedCourseRoute::subSection(
                $d['course'],
                $d['section'],
                $d['subSection']
            )), [
                'title' => 'Sub Renamed',
            ])
            ->assertRedirect();

        $this->assertEquals('Sub Renamed', $d['subSection']->fresh()->title);
    }

    public function test_destroy_routes_still_work_with_raw_ids(): void
    {
        $d = $this->structure();

        $this->actingAs($d['admin'])
            ->delete(route('admin.courses.section-lessons.destroy', NestedCourseRoute::sectionLessonDestroy(
                $d['course'],
                $d['section'],
                $d['directLesson']
            )))
            ->assertRedirect(route('admin.courses.show', $d['course']->id));

        $this->actingAs($d['teacher'])
            ->delete(route('teacher.lessons.destroy', NestedCourseRoute::subSectionLessonDestroy(
                $d['course'],
                $d['section'],
                $d['subSection'],
                $d['subLesson']
            )))
            ->assertRedirect(route('teacher.courses.show', $d['course']->id));
    }
}
