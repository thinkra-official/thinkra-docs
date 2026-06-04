<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chapters') && ! Schema::hasTable('sections')) {
            Schema::rename('chapters', 'sections');
        }

        if (! Schema::hasTable('sub_sections')) {
            Schema::create('sub_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('lessons', 'chapter_id')) {
            Schema::table('lessons', function (Blueprint $table) {
                if (! Schema::hasColumn('lessons', 'sub_section_id')) {
                    $table->unsignedBigInteger('sub_section_id')->nullable()->after('id');
                }
            });

            $sections = DB::table('sections')->orderBy('id')->get();

            foreach ($sections as $section) {
                $subSectionId = DB::table('sub_sections')->insertGetId([
                    'section_id' => $section->id,
                    'title' => 'محتوى القسم',
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('lessons')
                    ->where('chapter_id', $section->id)
                    ->update(['sub_section_id' => $subSectionId]);
            }

            Schema::table('lessons', function (Blueprint $table) {
                $table->dropForeign(['chapter_id']);
                $table->dropColumn('chapter_id');
            });
        }

        if (Schema::hasColumn('lessons', 'sub_section_id') && ! $this->foreignKeyExists('lessons', 'lessons_sub_section_id_foreign')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->foreign('sub_section_id')
                    ->references('id')
                    ->on('sub_sections')
                    ->cascadeOnDelete();
            });
        }
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        if ($connection->getDriverName() === 'sqlite') {
            return false;
        }

        return (bool) DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    public function down(): void
    {
        if (Schema::hasColumn('lessons', 'sub_section_id')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropForeign(['sub_section_id']);
                $table->unsignedBigInteger('chapter_id')->nullable()->after('id');
            });

            $subSections = DB::table('sub_sections')->get();
            foreach ($subSections as $subSection) {
                DB::table('lessons')
                    ->where('sub_section_id', $subSection->id)
                    ->update(['chapter_id' => $subSection->section_id]);
            }

            Schema::table('lessons', function (Blueprint $table) {
                $table->dropColumn('sub_section_id');
                $table->foreign('chapter_id')->references('id')->on('sections')->cascadeOnDelete();
            });
        }

        Schema::dropIfExists('sub_sections');

        if (Schema::hasTable('sections') && ! Schema::hasTable('chapters')) {
            Schema::rename('sections', 'chapters');
        }
    }
};
