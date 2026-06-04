<?php

namespace Tests\Feature;

use App\Enums\CourseMemberRole;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseStructureReorderTest extends TestCase
{
    use RefreshDatabase;

    protected function createStructure(): array
    {
        $teacher = User::factory()->teacher()->create(['phone' => '0513000001']);

        $course = Course::create(['title' => 'Reorder Course', 'slug' => 'reorder-course']);
        $course->members()->attach($teacher->id, ['role' => CourseMemberRole::Owner->value]);

        $section1 = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
        $section2 = $course->sections()->create(['title' => 'S2', 'sort_order' => 2]);

        $sub1 = $section1->subSections()->create(['title' => 'Sub1', 'sort_order' => 1]);
        $sub2 = $section1->subSections()->create(['title' => 'Sub2', 'sort_order' => 2]);

        $direct1 = $section1->directLessons()->create([
            'title' => 'Direct 1',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $direct2 = $section2->directLessons()->create([
            'title' => 'Direct 2',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $subLesson1 = $sub1->lessons()->create([
            'title' => 'Sub L1',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $subLesson2 = $sub2->lessons()->create([
            'title' => 'Sub L2',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        return compact(
            'teacher',
            'course',
            'section1',
            'section2',
            'sub1',
            'sub2',
            'direct1',
            'direct2',
            'subLesson1',
            'subLesson2'
        );
    }

    protected function fullPayload(array $data): array
    {
        return [
            'sections' => [
                [
                    'id' => $data['section2']->id,
                    'position' => 1,
                    'lessons' => [
                        ['id' => $data['direct2']->id, 'position' => 1],
                    ],
                    'subSections' => [],
                ],
                [
                    'id' => $data['section1']->id,
                    'position' => 2,
                    'lessons' => [
                        ['id' => $data['direct1']->id, 'position' => 1],
                    ],
                    'subSections' => [
                        [
                            'id' => $data['sub2']->id,
                            'position' => 1,
                            'lessons' => [
                                ['id' => $data['subLesson2']->id, 'position' => 1],
                            ],
                        ],
                        [
                            'id' => $data['sub1']->id,
                            'position' => 2,
                            'lessons' => [
                                ['id' => $data['subLesson1']->id, 'position' => 1],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_admin_reorder_sections_sub_sections_and_lessons(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $payload = $this->fullPayload($data);
        $payload['sections'][1]['lessons'] = [
            ['id' => $data['direct1']->id, 'position' => 1],
        ];
        $payload['sections'][1]['subSections'][0]['lessons'] = [];
        $payload['sections'][1]['subSections'][1]['lessons'] = [
            ['id' => $data['subLesson1']->id, 'position' => 1],
            ['id' => $data['subLesson2']->id, 'position' => 2],
        ];

        $this->actingAs($admin)
            ->patchJson(route('admin.courses.structure.reorder', ['courseId' => $data['course']->id]), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertEquals(1, $data['section2']->fresh()->sort_order);
        $this->assertEquals(2, $data['section1']->fresh()->sort_order);
        $this->assertEquals(1, $data['sub2']->fresh()->sort_order);
        $this->assertEquals(2, $data['sub1']->fresh()->sort_order);
    }

    public function test_teacher_reorder_without_404(): void
    {
        $data = $this->createStructure();

        $this->actingAs($data['teacher'])
            ->patchJson(route('teacher.courses.structure.reorder', ['courseId' => $data['course']->id]), $this->fullPayload($data))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_move_lesson_from_section_to_sub_section(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $payload = $this->fullPayload($data);
        $payload['sections'][1]['lessons'] = [];
        $payload['sections'][1]['subSections'][0]['lessons'] = [
            ['id' => $data['subLesson2']->id, 'position' => 1],
        ];
        $payload['sections'][1]['subSections'][1]['lessons'] = [
            ['id' => $data['direct1']->id, 'position' => 1],
            ['id' => $data['subLesson1']->id, 'position' => 2],
        ];

        $this->actingAs($admin)
            ->patchJson(route('admin.courses.structure.reorder', ['courseId' => $data['course']->id]), $payload)
            ->assertOk();

        $direct = $data['direct1']->fresh();
        $this->assertNull($direct->section_id);
        $this->assertEquals($data['sub1']->id, $direct->sub_section_id);
    }

    public function test_move_lesson_from_sub_section_to_direct_section(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $payload = $this->fullPayload($data);
        $payload['sections'][1]['lessons'] = [
            ['id' => $data['subLesson1']->id, 'position' => 1],
            ['id' => $data['direct1']->id, 'position' => 2],
        ];
        $payload['sections'][1]['subSections'][0]['lessons'] = [
            ['id' => $data['subLesson2']->id, 'position' => 1],
        ];
        $payload['sections'][1]['subSections'][1]['lessons'] = [];

        $this->actingAs($admin)
            ->patchJson(route('admin.courses.structure.reorder', ['courseId' => $data['course']->id]), $payload)
            ->assertOk();

        $lesson = $data['subLesson1']->fresh();
        $this->assertEquals($data['section1']->id, $lesson->section_id);
        $this->assertNull($lesson->sub_section_id);
    }

    public function test_move_lesson_between_sections(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $payload = $this->fullPayload($data);
        $payload['sections'][0]['lessons'] = [
            ['id' => $data['direct1']->id, 'position' => 1],
            ['id' => $data['direct2']->id, 'position' => 2],
        ];
        $payload['sections'][1]['lessons'] = [];
        $payload['sections'][1]['subSections'][0]['lessons'] = [
            ['id' => $data['subLesson2']->id, 'position' => 1],
        ];
        $payload['sections'][1]['subSections'][1]['lessons'] = [
            ['id' => $data['subLesson1']->id, 'position' => 1],
        ];

        $this->actingAs($admin)
            ->patchJson(route('admin.courses.structure.reorder', ['courseId' => $data['course']->id]), $payload)
            ->assertOk();

        $lesson = $data['direct1']->fresh();
        $this->assertEquals($data['section2']->id, $lesson->section_id);
        $this->assertNull($lesson->sub_section_id);
    }

    public function test_reorder_rejects_lesson_from_other_course(): void
    {
        $data = $this->createStructure();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $otherCourse = Course::create(['title' => 'Other', 'slug' => 'other-reorder']);
        $otherSection = $otherCourse->sections()->create(['title' => 'X', 'sort_order' => 1]);
        $otherLesson = $otherSection->directLessons()->create([
            'title' => 'Other lesson',
            'status' => LessonStatus::Draft,
            'sort_order' => 1,
        ]);

        $payload = $this->fullPayload($data);
        $payload['sections'][1]['lessons'][] = ['id' => $otherLesson->id, 'position' => 99];

        $this->actingAs($admin)
            ->patchJson(route('admin.courses.structure.reorder', ['courseId' => $data['course']->id]), $payload)
            ->assertStatus(422);
    }

    public function test_viewer_cannot_reorder(): void
    {
        $data = $this->createStructure();
        $viewer = User::factory()->teacher()->create(['phone' => '0513000099']);
        $data['course']->members()->attach($viewer->id, ['role' => CourseMemberRole::Viewer->value]);

        $this->actingAs($viewer)
            ->patchJson(route('teacher.courses.structure.reorder', ['courseId' => $data['course']->id]), $this->fullPayload($data))
            ->assertForbidden();
    }
}
