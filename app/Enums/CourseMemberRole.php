<?php

namespace App\Enums;

enum CourseMemberRole: string
{
    case Owner = 'OWNER';
    case Editor = 'EDITOR';
    case Viewer = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
        };
    }

    public function canEditContent(): bool
    {
        return in_array($this, [self::Owner, self::Editor], true);
    }

    public function canManageCourse(): bool
    {
        return $this === self::Owner;
    }
}
