<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\CourseVisibility;
use App\Enums\LessonStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsLessonProgressTest extends TestCase
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

        return compact('course', 'lesson');
    }

    public function test_authenticated_user_can_complete_and_uncomplete_lesson(): void
    {
        $data = $this->createPublishedCourse();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('docs.lesson.complete', ['post-production', 'what-is-editing']))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonFragment(['completedIds' => [$data['lesson']->id]]);

        $this->assertDatabaseHas('lesson_user_progress', [
            'user_id' => $user->id,
            'lesson_id' => $data['lesson']->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('docs.lesson.uncomplete', ['post-production', 'what-is-editing']))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('completedIds', []);

        $this->assertDatabaseMissing('lesson_user_progress', [
            'user_id' => $user->id,
            'lesson_id' => $data['lesson']->id,
        ]);
    }

    public function test_uncomplete_requires_authentication(): void
    {
        $this->createPublishedCourse();

        $this->postJson(route('docs.lesson.uncomplete', ['post-production', 'what-is-editing']))
            ->assertUnauthorized();
    }
}
