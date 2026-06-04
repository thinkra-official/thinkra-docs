<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'title',
        'sort_order',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)
            ->whereNull('section_id')
            ->orderBy('sort_order');
    }

    public function resolveChildRouteBinding($childType, $value, $field = null)
    {
        if ($childType === 'lesson') {
            $field = $field ?: 'id';

            return $this->lessons()->where($field, $value)->first();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }
}
