<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicAdSetting extends Model
{
    protected $fillable = ['podcast_id','status','default_fill','pre_total','mid_total','post_total','targets'];
    protected $casts = ['targets' => 'array'];
}

