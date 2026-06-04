<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'sub_section_id',
        'title',
        'slug',
        'objective',
        'main_content',
        'teacher_notes',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => LessonStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected static function booted(): void
    {
        static::saving(function (Lesson $lesson) {
            if (in_array($lesson->sub_section_id, [0, '0'], true)) {
                $lesson->sub_section_id = null;
            }

            $hasSection = $lesson->section_id !== null && $lesson->section_id !== 0;
            $hasSubSection = $lesson->sub_section_id !== null && $lesson->sub_section_id !== 0;

            if ($hasSection === $hasSubSection) {
                throw ValidationException::withMessages([
                    'placement' => ['يجب ربط الدرس بفصل أو بقسم فرعي، وليس الاثنين معاً.'],
                ]);
            }

            if (empty($lesson->slug) && ! empty($lesson->title)) {
                $lesson->slug = static::uniqueSlugForCourse($lesson);
            }
        });
    }

    public static function uniqueSlugForCourse(Lesson $lesson): string
    {
        $courseId = $lesson->resolveCourse()->id;
        $base = Str::slug($lesson->title) ?: 'lesson';

        $slug = $base;
        $n = 2;

        while (static::query()
            ->where('slug', $slug)
            ->where('id', '!=', $lesson->id ?? 0)
            ->where(function (Builder $q) use ($courseId) {
                $q->whereHas('section', fn ($sq) => $sq->where('course_id', $courseId))
                    ->orWhereHas('subSection.section', fn ($sq) => $sq->where('course_id', $courseId));
            })
            ->exists()) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', LessonStatus::Published);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subSection(): BelongsTo
    {
        return $this->belongsTo(SubSection::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LessonVersion::class)->orderByDesc('created_at');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(LessonChangeLog::class)->orderByDesc('created_at');
    }

    public function isDirectInSection(): bool
    {
        return $this->section_id !== null && $this->sub_section_id === null;
    }

    public function isInSubSection(): bool
    {
        return $this->sub_section_id !== null && $this->section_id === null;
    }

    public function isPublished(): bool
    {
        return $this->status === LessonStatus::Published;
    }

    public function resolveCourse(): Course
    {
        if ($this->isDirectInSection()) {
            $this->loadMissing('section.course');

            return $this->section->course;
        }

        $this->loadMissing('subSection.section.course');

        return $this->subSection->section->course;
    }

    public function resolveSection(): Section
    {
        if ($this->isDirectInSection()) {
            $this->loadMissing('section');

            return $this->section;
        }

        $this->loadMissing('subSection.section');

        return $this->subSection->section;
    }
}
