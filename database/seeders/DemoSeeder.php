<?php

namespace Database\Seeders;

use App\Enums\CourseMemberRole;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\SubSection;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) && ! env('SEED_DEMO_DATA', false)) {
            return;
        }

        $teacher = User::firstOrCreate(
            ['phone' => '0500000001'],
            [
                'name' => 'أستاذ تجريبي',
                'password' => 'teacher123',
                'role' => UserRole::Teacher,
                'is_active' => true,
            ]
        );

        $course = Course::firstOrCreate(
            ['slug' => 'intro-programming'],
            [
                'title' => 'مقدمة في البرمجة',
                'description' => 'كورس تجريبي لاختبار Thinkra Docs',
            ]
        );

        $course->members()->syncWithoutDetaching([
            $teacher->id => ['role' => CourseMemberRole::Owner->value],
        ]);

        $section = Section::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'القسم الأول'],
            ['sort_order' => 1]
        );

        $subSection = SubSection::firstOrCreate(
            ['section_id' => $section->id, 'title' => 'أساسيات البرمجة'],
            ['sort_order' => 1]
        );

        Lesson::firstOrCreate(
            ['sub_section_id' => $subSection->id, 'title' => 'ما هي البرمجة؟'],
            [
                'objective' => 'فهم مفهوم البرمجة',
                'main_content' => '<p>محتوى تجريبي للدرس الأول.</p>',
                'status' => LessonStatus::Draft,
                'sort_order' => 1,
            ]
        );
    }
}
