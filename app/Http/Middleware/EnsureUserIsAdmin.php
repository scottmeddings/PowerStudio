<?php
// app/Http/Middleware/EnsureUserIsAdmin.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && $user->isAdmin(), 403);
        return $next($request);
    }
}
