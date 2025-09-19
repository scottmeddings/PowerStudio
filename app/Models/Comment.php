<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends TenantModel
{
    use HasFactory;

    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING  = 'pending';
    public const STATUS_SPAM     = 'spam';

    protected $fillable = ['episode_id', 'user_id', 'body', 'status'];
    protected $casts = [
        'episode_id' => 'int',
        'user_id'    => 'int',
        'status'     => 'string',
    ];

    public function episode() { return $this->belongsTo(Episode::class); }
    public function user()    { return $this->belongsTo(User::class); }

    public function scopeApproved($q) { return $q->where('status', self::STATUS_APPROVED); }

    // IMPORTANT: match signature + call parent
    protected static function booted(): void
    {
        parent::booted();

        // increment when an approved comment is created
        static::created(function (Comment $c) {
            if ($c->status === self::STATUS_APPROVED) {
                $c->episode()?->increment('comments_count');
            }
        });

        // decrement when an approved comment is deleted
        static::deleted(function (Comment $c) {
            if ($c->status === self::STATUS_APPROVED) {
                $c->episode()?->decrement('comments_count');
            }
        });

        // handle status changes (pending <-> approved)
        static::updated(function (Comment $c) {
            $was = $c->getOriginal('status');
            $now = $c->status;

            if ($was !== self::STATUS_APPROVED && $now === self::STATUS_APPROVED) {
                $c->episode()?->increment('comments_count');
            } elseif ($was === self::STATUS_APPROVED && $now !== self::STATUS_APPROVED) {
                $c->episode()?->decrement('comments_count');
            }

            // If episode_id changed and comment is approved, move the count across
            if ($c->isDirty('episode_id') && $now === self::STATUS_APPROVED) {
                $oldId = $c->getOriginal('episode_id');
                if ($oldId) Episode::whereKey($oldId)->decrement('comments_count');
                $c->episode()?->increment('comments_count');
            }
        });
    }
}
