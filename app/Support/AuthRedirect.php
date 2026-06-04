<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;

class AuthRedirect
{
    public static function homeFor(?User $user): string
    {
        if (! $user) {
            return route('teacher.login');
        }

        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }

        if ($user->role === UserRole::Teacher) {
            return route('teacher.dashboard');
        }

        return route('teacher.login');
    }

    /** Where authenticated users go when they hit a guest-only route (e.g. login). */
    public static function forAuthenticatedGuest(Request $request): string
    {
        $user = $request->user();

        if ($request->is('admin', 'admin/*')) {
            return $user?->isAdmin()
                ? route('admin.dashboard')
                : route('teacher.dashboard');
        }

        return self::homeFor($user);
    }
}
