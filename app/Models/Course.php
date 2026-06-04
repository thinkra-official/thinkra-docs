<?php

namespace App\Models;

use App\Enums\CourseMemberRole;
use App\Enums\CourseVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'visibility',
        'require_login',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => CourseVisibility::class,
            'require_login' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Course $course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title);
            }
        });
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function teachers(): BelongsToMany
    {
        return $this->members()->where('users.role', \App\Enums\UserRole::Teacher);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort_order');
    }

    public function resolveChildRouteBinding($childType, $value, $field = null): ?Model
    {
        if ($childType === 'section') {
            return $this->sections()->whereKey($value)->first();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(CourseShareLink::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === CourseVisibility::Public;
    }

    public function isUnlisted(): bool
    {
        return $this->visibility === CourseVisibility::Unlisted;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === CourseVisibility::Private;
    }

    public function memberRoleFor(User $user): ?CourseMemberRole
    {
        $member = $this->members()->where('users.id', $user->id)->first();

        if (! $member) {
            return null;
        }

        return CourseMemberRole::from($member->pivot->role);
    }
}
