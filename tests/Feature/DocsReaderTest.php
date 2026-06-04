<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\CourseVisibility;
use App\Enums\LessonStatus;
use App\Models\Course;
use App\Models\CourseShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DocsReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function createPublishedCourse(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0501111001']);

        $course = Course::create([
            'title' => 'Post Production',
            'slug' => 'post-production',
            'visibility' => CourseVisibility::Public,
            'require_login' => false,
        ]);

        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section = $course->sections()->create(['title' => 'Chapter 1', 'sort_order' => 1]);
        $lesson = $section->directLessons()->create([
            'title' => 'What is Editing',
            'slug' => 'what-is-editing',
            'status' => LessonStatus::Published,
            'sort_order' => 1,
        ]);

        return compact('teacher', 'course', 'section', 'lesson');
    }

    public function test_public_lesson_page_is_accessible(): void
    {
        $data = $this->createPublishedCourse();

        $this->get(route('docs.lesson', ['post-production', 'what-is-editing']))
            ->assertOk()
            ->assertSee('What is Editing');
    }

    public function test_private_course_returns_not_found_for_guest(): void
    {
        $data = $this->createPublishedCourse();
        $data['course']->update(['visibility' => CourseVisibility::Private]);

        $this->get(route('docs.lesson', ['post-production', 'what-is-editing']))
            ->assertNotFound();
    }

    public function test_unlisted_course_accessible_by_direct_url(): void
    {
        $data = $this->createPublishedCourse();
        $data['course']->update(['visibility' => CourseVisibility::Unlisted]);

        $this->get(route('docs.lesson', ['post-production', 'what-is-editing']))
            ->assertOk();
    }

    public function test_draft_lesson_not_visible_in_public_reader(): void
    {
        $data = $this->createPublishedCourse();
        $data['lesson']->update(['status' => LessonStatus::Draft]);

        $this->get(route('docs.lesson', ['post-production', 'what-is-editing']))
            ->assertNotFound();
    }

    public function test_share_link_grants_access_to_private_course(): void
    {
        $data = $this->createPublishedCourse();
        $data['course']->update(['visibility' => CourseVisibility::Private]);

        $link = CourseShareLink::create([
            'course_id' => $data['course']->id,
            'token' => 'test-share-token-123',
            'expires_at' => now()->addDay(),
        ]);

        $this->get(route('share.show', $link->token))
            ->assertRedirect(route('docs.lesson', ['post-production', 'what-is-editing']));

        $this->get(route('docs.lesson', ['post-production', 'what-is-editing']))
            ->assertOk();
    }

    public function test_expired_share_link_shows_expired_page(): void
    {
        $data = $this->createPublishedCourse();
        $data['course']->update(['visibility' => CourseVisibility::Private]);

        $link = CourseShareLink::create([
            'course_id' => $data['course']->id,
            'token' => 'expired-token',
            'expires_at' => now()->subDay(),
        ]);

        $this->get(route('share.show', $link->token))
            ->assertOk()
            ->assertSee('منتهي الصلاحية');
    }

    public function test_preview_shows_draft_for_course_teacher(): void
    {
        $data = $this->createPublishedCourse();
        $data['lesson']->update(['status' => LessonStatus::Draft]);

        $this->actingAs($data['teacher'])
            ->get(route('docs.preview.lesson', ['post-production', 'what-is-editing']))
            ->assertOk()
            ->assertSee('What is Editing');
    }

    public function test_search_only_finds_public_courses(): void
    {
        $this->createPublishedCourse();
        Course::create([
            'title' => 'Secret Internal',
            'slug' => 'secret-internal',
            'visibility' => CourseVisibility::Private,
        ]);

        $this->get(route('docs.search', ['q' => 'Post']))
            ->assertOk()
            ->assertSee('Post Production')
            ->assertDontSee('Secret Internal');
    }

    public function test_require_login_redirects_guest(): void
    {
        $data = $this->createPublishedCourse();
        $data['course']->update(['require_login' => true]);

        $this->get(route('docs.lesson', ['post-production', 'what-is-editing']))
            ->assertRedirect();
    }

    public function test_admin_can_update_docs_settings(): void
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);
        $data = $this->createPublishedCourse();

        $this->actingAs($admin)
            ->put(route('admin.courses.docs.update', $data['course']), [
                'visibility' => CourseVisibility::Unlisted->value,
                'require_login' => '1',
            ])
            ->assertRedirect(route('admin.courses.docs.edit', $data['course']))
            ->assertSessionHas('success');

        $data['course']->refresh();
        $this->assertEquals(CourseVisibility::Unlisted, $data['course']->visibility);
        $this->assertTrue($data['course']->require_login);
    }
}
