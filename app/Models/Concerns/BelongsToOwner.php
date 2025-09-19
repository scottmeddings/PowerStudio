<?php
// app/Models/Concerns/BelongsToOwner.php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToOwner
{
    protected static function bootBelongsToOwner(): void
    {
        static::creating(function ($model) {
            if (!$model->user_id && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function scopeOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->where($q->getModel()->getTable().'.user_id', $userId);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

