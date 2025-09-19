<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EnsureAdminLike
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // If not logged in, send them to login instead of 403
        if (!$user) {
            return redirect()->guest(route('login'));
        }

        // --- BOOTSTRAP BYPASS (owner gets in so they can invite others) ---
        // 1) Allow configured owner email (optional)
        $ownerEmail = config('app.owner_email') ?? env('APP_OWNER_EMAIL');
        if ($ownerEmail && strcasecmp($user->email ?? '', $ownerEmail) === 0) {
            return $next($request);
        }

        // 2) Allow the very first user in the system (id smallest) – one-time bootstrap
        if (Schema::hasTable('users')) {
            $firstId = \App\Models\User::query()->min('id');
            if ($firstId && (int)$user->id === (int)$firstId) {
                return $next($request);
            }
        }

        // 3) If there are zero collaborators yet, allow any authenticated user to set up the first one
        $noCollaboratorsYet = Schema::hasTable('collaborators')
            ? !\App\Models\Collaborator::query()->exists()
            : true; // if table not created yet, also allow
        if ($noCollaboratorsYet) {
            return $next($request);
        }
        // --- END BOOTSTRAP BYPASS ---

        // Regular checks
        $isAdmin = (property_exists($user, 'is_admin') && (bool)$user->is_admin)
                || (method_exists($user, 'is_admin') && (bool)$user->is_admin);

        $isCollaborator = false;
        if (Schema::hasTable('collaborators')) {
            $email = strtolower($user->email ?? '');
            $isCollaborator = \App\Models\Collaborator::query()
                ->where(function ($q) use ($user, $email) {
                    $q->where('user_id', $user->id ?? 0)
                      ->orWhereRaw('LOWER(email) = ?', [$email]);
                })
                ->where('role', 'admin')
                ->whereNotNull('accepted_at')
                ->exists();
        }

        if (!($isAdmin || $isCollaborator)) {
            abort(403);
        }

        return $next($request);
    }
}
