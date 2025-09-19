<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collaborator extends \App\Models\TenantModel
{
    protected $fillable = [
        'email','user_id','invited_by','role','token','accepted_at'
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function inviter(): BelongsTo { return $this->belongsTo(User::class, 'invited_by'); }
}
