<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }
        });

        // Existing lessons stay under sub-sections only (sub_section_id set, section_id null).
        // No backfill of section_id from sub_sections — XOR placement.

        if (Schema::hasColumn('lessons', 'sub_section_id')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->foreignId('sub_section_id')->nullable()->change();
            });
        }

        if (! $this->indexExists('lessons', 'lessons_section_sort_index')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->index(['section_id', 'sort_order'], 'lessons_section_sort_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('lessons', 'lessons_section_sort_index')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropIndex('lessons_section_sort_index');
            });
        }

        // Direct section lessons cannot exist after rollback if any were created.
        DB::table('lessons')->whereNotNull('section_id')->whereNull('sub_section_id')->delete();

        if (Schema::hasColumn('lessons', 'section_id')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropForeign(['section_id']);
                $table->dropColumn('section_id');
            });
        }

        if (Schema::hasColumn('lessons', 'sub_section_id')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->foreignId('sub_section_id')->nullable(false)->change();
            });
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? '') === $index);
        }

        $database = $connection->getDatabaseName();

        return (bool) DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }
};
