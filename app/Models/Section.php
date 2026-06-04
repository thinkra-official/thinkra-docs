<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'sort_order',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subSections(): HasMany
    {
        return $this->hasMany(SubSection::class)->orderBy('sort_order');
    }

    /** دروس مباشرة تحت الفصل (بدون قسم فرعي) — للـ scoped route binding */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)
            ->whereNull('sub_section_id')
            ->orderBy('sort_order');
    }

    public function directLessons(): HasMany
    {
        return $this->lessons();
    }

    /**
     * Scoped route binding: only direct lessons (section_id set, no sub-section).
     */
    public function resolveChildRouteBinding($childType, $value, $field = null)
    {
        if ($childType === 'lesson') {
            $field = $field ?: 'id';

            return $this->directLessons()->where($field, $value)->first();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }
}
