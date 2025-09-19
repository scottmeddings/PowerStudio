<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseCampaign extends \App\Models\TenantModel
{
    protected $fillable = ['podcast_id','name','status','start_at','end_at','priority','targets'];
    protected $casts = ['start_at'=>'date','end_at'=>'date','targets'=>'array'];

    public function promos() { return $this->hasMany(HousePromo::class, 'campaign_id'); }
}
