<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || $user->role !== UserRole::Teacher) {
            abort(403, 'هذه المنطقة مخصصة للأساتذة فقط.');
        }

        return $next($request);
    }
}
