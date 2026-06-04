<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('visibility', 20)->default('PRIVATE')->after('description');
            $table->boolean('require_login')->default(false)->after('visibility');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->index('slug');
        });

        $this->backfillLessonSlugs();

        Schema::create('course_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    protected function backfillLessonSlugs(): void
    {
        $lessons = \App\Models\Lesson::query()->with(['section', 'subSection.section'])->get();

        foreach ($lessons->groupBy(fn ($l) => $l->resolveCourse()->id) as $courseLessons) {
            $used = [];
            foreach ($courseLessons as $lesson) {
                $base = \Illuminate\Support\Str::slug($lesson->title) ?: 'lesson-'.$lesson->id;
                $slug = $base;
                $n = 2;
                while (isset($used[$slug])) {
                    $slug = $base.'-'.$n;
                    $n++;
                }
                $used[$slug] = true;
                \Illuminate\Support\Facades\DB::table('lessons')
                    ->where('id', $lesson->id)
                    ->update(['slug' => $slug]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_share_links');

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'require_login']);
        });
    }
};
