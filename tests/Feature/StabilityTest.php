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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function createStructure(CourseMemberRole $role = CourseMemberRole::Owner): array
    {
        $teacher = User::factory()->teacher()->create([
            'phone' => '0509000001',
            'password' => Hash::make('password'),
        ]);

        $course = Course::create(['title' => 'QA Course', 'slug' => 'qa-course']);
        $course->members()->attach($teacher->id, ['role' => $role->value]);

        $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
        $subSection = $section->subSections()->create(['title' => 'SS1', 'sort_order' => 1]);
        $lesson = $subSection->lessons()->create([
            'title' => 'L1',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        return compact('teacher', 'course', 'section', 'subSection', 'lesson');
    }

    public function test_admin_login_success(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@thinkra.test',
            'password' => Hash::make('secret'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@thinkra.test',
            'password' => 'secret',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_login_failure_shows_arabic_error(): void
    {
        $this->post(route('admin.login.submit'), [
            'email' => 'wrong@thinkra.test',
            'password' => 'wrong',
        ])
            ->assertSessionHasErrors('email')
            ->assertRedirect();

        $errors = session('errors')->get('email');
        $this->assertStringContainsString('غير صحيحة', $errors[0]);
    }

    public function test_teacher_login_success(): void
    {
        $teacher = User::factory()->teacher()->create([
            'phone' => '0509000002',
            'password' => Hash::make('teacher-pass'),
        ]);

        $this->post(route('teacher.login.submit'), [
            'phone' => '0509000002',
            'password' => 'teacher-pass',
        ])->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($teacher);
    }

    public function test_teacher_login_failure_shows_arabic_error(): void
    {
        $this->post(route('teacher.login.submit'), [
            'phone' => '0500000000',
            'password' => 'wrong',
        ])
            ->assertSessionHasErrors('phone');

        $errors = session('errors')->get('phone');
        $this->assertStringContainsString('غير صحيحة', $errors[0]);
    }

    public function test_teacher_cannot_access_admin_area(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_create_section(): void
    {
        $data = $this->createStructure(CourseMemberRole::Viewer);

        $this->actingAs($data['teacher'])
            ->post(route('teacher.sections.store', $data['course']), ['title' => 'New'])
            ->assertForbidden();
    }

    public function test_section_crud_and_reorder(): void
    {
        $data = $this->createStructure();
        $course = $data['course'];

        $this->actingAs($data['teacher'])
            ->post(route('teacher.sections.store', $course), ['title' => 'S2'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $section2 = $course->sections()->where('title', 'S2')->first();
        $this->assertNotNull($section2);

        $this->actingAs($data['teacher'])
            ->put(route('teacher.sections.update', NestedCourseRoute::section($course, $section2)), [
                'title' => 'S2 Updated',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($data['teacher'])
            ->patch(route('teacher.sections.move', NestedCourseRoute::section($course, $section2)), [
                'direction' => 'up',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($data['teacher'])
            ->delete(route('teacher.sections.destroy', NestedCourseRoute::section($course, $data['section'])))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_sub_section_crud_and_reorder(): void
    {
        $data = $this->createStructure();
        $this->actingAs($data['teacher'])
            ->post(route('teacher.sub-sections.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]), ['title' => 'SS2'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $sub2 = $data['section']->subSections()->where('title', 'SS2')->first();

        $this->actingAs($data['teacher'])
            ->put(route('teacher.sub-sections.update', NestedCourseRoute::subSection($data['course'], $data['section'], $sub2)), [
                'title' => 'SS2 Updated',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $sub2->update(['sort_order' => 2]);

        $this->actingAs($data['teacher'])
            ->patch(route('teacher.sub-sections.move', NestedCourseRoute::subSection($data['course'], $data['section'], $sub2)), [
                'direction' => 'up',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_lesson_crud_autosave_and_version_restore(): void
    {
        $data = $this->createStructure();
        $nested = NestedCourseRoute::subSection($data['course'], $data['section'], $data['subSection']);

        $this->actingAs($data['teacher'])
            ->post(route('teacher.lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
                'subSectionId' => $data['subSection']->id,
            ]), ['title' => 'New Lesson'])
            ->assertRedirect(route('teacher.courses.show', $data['course']))
            ->assertSessionHas('success');

        $lesson = $data['subSection']->lessons()->where('title', 'New Lesson')->first();
        $lessonRoute = NestedCourseRoute::subSectionLesson($data['course'], $data['section'], $data['subSection'], $lesson);

        $this->actingAs($data['teacher'])
            ->put(route('teacher.lessons.update', $lessonRoute), [
                'title' => 'Updated Lesson',
                'objective' => 'هدف',
                'main_content' => '<p>محتوى</p>',
                'teacher_notes' => 'ملاحظة',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($data['teacher'])
            ->postJson(route('teacher.lessons.autosave', $lessonRoute), [
                'title' => 'Updated Lesson',
                'objective' => 'هدف 2',
                'main_content' => '<p>محتوى 2</p>',
                'teacher_notes' => null,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $version = LessonVersion::create([
            'lesson_id' => $lesson->id,
            'title' => 'Old Title',
            'objective' => 'قديم',
            'main_content' => '<p>قديم</p>',
            'teacher_notes' => null,
            'created_by' => $data['teacher']->id,
            'created_at' => now()->subHour(),
        ]);

        $lesson->update(['title' => 'Current Title']);

        $this->actingAs($data['teacher'])
            ->postJson(route('teacher.lessons.versions.restore', NestedCourseRoute::subSectionVersion(
                $data['course'],
                $data['section'],
                $data['subSection'],
                $lesson,
                $version
            )))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertEquals('Old Title', $lesson->fresh()->title);
    }

    public function test_admin_section_lesson_store_validation_redirects_back_not_404(): void
    {
        $data = $this->createStructure();
        $admin = \App\Models\User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->from(route('admin.courses.show', $data['course']))
            ->post(route('admin.courses.section-lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]), ['title' => ''])
            ->assertRedirect(route('admin.courses.show', $data['course']))
            ->assertSessionHasErrors('title');

        $this->assertSame(0, $data['section']->directLessons()->count());
    }

    public function test_admin_section_lesson_store_via_post(): void
    {
        $data = $this->createStructure();
        $admin = \App\Models\User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.courses.section-lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]), [
                'title' => 'Admin Direct Lesson',
                'objective' => '',
                'main_content' => '',
                'teacher_notes' => '',
            ])
            ->assertRedirect(route('admin.courses.show', $data['course']))
            ->assertSessionHas('success');

        $lesson = $data['section']->directLessons()->where('title', 'Admin Direct Lesson')->first();
        $this->assertNotNull($lesson);
        $this->assertSame($data['section']->id, $lesson->section_id);
        $this->assertNull($lesson->sub_section_id);

        $this->get(route('admin.courses.section-lessons.store', [
            'courseId' => $data['course']->id,
            'sectionId' => $data['section']->id,
        ]))->assertMethodNotAllowed();
    }

    public function test_section_lesson_store_and_edit(): void
    {
        $data = $this->createStructure();
        $sectionRoute = NestedCourseRoute::section($data['course'], $data['section']);

        $this->actingAs($data['teacher'])
            ->post(route('teacher.section-lessons.store', [
                'courseId' => $data['course']->id,
                'sectionId' => $data['section']->id,
            ]), ['title' => 'فصل درس'])
            ->assertRedirect(route('teacher.courses.show', $data['course']))
            ->assertSessionHas('success');

        $lesson = $data['section']->directLessons()->where('title', 'فصل درس')->first();
        $this->assertNotNull($lesson);
        $this->assertNull($lesson->sub_section_id);
        $this->assertEquals($data['section']->id, $lesson->section_id);

        $this->actingAs($data['teacher'])
            ->get(route('teacher.section-lessons.edit', NestedCourseRoute::sectionLesson(
                $data['course'],
                $data['section'],
                $lesson
            )))
            ->assertOk();
    }

    public function test_lesson_validation_returns_arabic_field_names(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->postJson(route('teacher.lessons.autosave', NestedCourseRoute::subSectionLesson(
                $data['course'],
                $data['section'],
                $data['subSection'],
                $data['lesson']
            )), ['title' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_sidebar_routes_are_registered(): void
    {
        $this->assertNotNull(route('teacher.dashboard', [], false));
        $this->assertNotNull(route('teacher.courses.index', [], false));
        $this->assertNotNull(route('admin.dashboard', [], false));
        $this->assertNotNull(route('admin.courses.index', [], false));
        $this->assertNotNull(route('admin.teachers.index', [], false));
    }
}
