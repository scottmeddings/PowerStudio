<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasskeyCredential extends Model
{
    protected $fillable = [
        'user_id','credential_id','public_key','label','transports','sign_count',
    ];
}
