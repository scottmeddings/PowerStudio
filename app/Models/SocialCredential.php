<?php
// app/Models/SocialCredential.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialCredential extends Model
{
    protected $fillable = ['user_id','provider','credentials','connected_at'];
    protected $casts = [
        'credentials'  => 'encrypted:array',
        'connected_at' => 'datetime',
    ];
}
