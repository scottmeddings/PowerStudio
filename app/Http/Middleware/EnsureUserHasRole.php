<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) abort(403);

        if (empty($roles) || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}