<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HousePromo extends Model
{
    protected $fillable = ['campaign_id','label','slot','audio_url','cta_url','episodes'];
    protected $casts = ['episodes'=>'array'];

    public function campaign() { return $this->belongsTo(HouseCampaign::class, 'campaign_id'); }
}
