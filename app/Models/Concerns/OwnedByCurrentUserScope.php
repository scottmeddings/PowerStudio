<?php
// app/Models/Concerns/OwnedByCurrentUserScope.php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class OwnedByCurrentUserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole()) return; // migrations, tinker, etc.
        $u = Auth::user();
        if (!$u) return;                      // guests (e.g., public pages)
        if (method_exists($u, 'isAdmin') && $u->isAdmin()) return;

        $builder->where($model->getTable().'.user_id', $u->id);
    }
}
