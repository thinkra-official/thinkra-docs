<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->index(['course_id', 'sort_order'], 'sections_course_sort_index');
        });

        Schema::table('sub_sections', function (Blueprint $table) {
            $table->index(['section_id', 'sort_order'], 'sub_sections_section_sort_index');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->index(['sub_section_id', 'sort_order'], 'lessons_sub_section_sort_index');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex('sections_course_sort_index');
        });

        Schema::table('sub_sections', function (Blueprint $table) {
            $table->dropIndex('sub_sections_section_sort_index');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('lessons_sub_section_sort_index');
        });
    }
};
