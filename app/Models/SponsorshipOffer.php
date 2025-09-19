<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorshipOffer extends \App\Models\TenantModel
{
    protected $fillable = [
        'podcast_id','title','cpm_usd','min_downloads',
        'pre_slots','mid_slots','post_slots','start_at','end_at','status','notes'
    ];
    protected $casts = ['start_at'=>'date','end_at'=>'date'];
}
