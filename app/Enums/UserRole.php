<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case Admin = 'ADMIN';
    case Teacher = 'TEACHER';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Teacher => 'Teacher',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }
}
