<?php

namespace Tests\Feature;

use App\Enums\CourseVisibility;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseShareLinkDestroyRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_course_share_link_without_404(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $course = Course::create([
            'title' => 'Share Link Course',
            'slug' => 'share-link-course',
            'visibility' => CourseVisibility::Private,
        ]);

        $link = CourseShareLink::create([
            'course_id' => $course->id,
            'token' => 'delete-me-token',
            'expires_at' => now()->addDay(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.share-links.destroy', [
                'courseId' => $course->id,
                'shareLinkId' => $link->id,
            ]))
            ->assertRedirect(route('admin.courses.docs.edit', $course))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('course_share_links', ['id' => $link->id]);
    }

    public function test_admin_share_link_destroy_returns_404_when_link_belongs_to_other_course(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $courseA = Course::create([
            'title' => 'Course A',
            'slug' => 'course-a',
            'visibility' => CourseVisibility::Private,
        ]);

        $courseB = Course::create([
            'title' => 'Course B',
            'slug' => 'course-b',
            'visibility' => CourseVisibility::Private,
        ]);

        $linkOnB = CourseShareLink::create([
            'course_id' => $courseB->id,
            'token' => 'belongs-to-b',
            'expires_at' => null,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.share-links.destroy', [
                'courseId' => $courseA->id,
                'shareLinkId' => $linkOnB->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseHas('course_share_links', ['id' => $linkOnB->id]);
    }
}
