<?php

namespace App\Enums;

enum CourseVisibility: string
{
    case Private = 'PRIVATE';
    case Unlisted = 'UNLISTED';
    case Public = 'PUBLIC';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'خاص',
            self::Unlisted => 'غير مدرج',
            self::Public => 'عام',
        };
    }
}
