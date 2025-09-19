<?php

// app/Models/AdInventory.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdInventory extends Model {
    protected $fillable = ['episode_id','pre_total','pre_sold','mid_total','mid_sold','post_total','post_sold','status'];
    public function episode(){ return $this->belongsTo(Episode::class); }
}

// app/Models/RevenueDaily.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RevenueDaily extends Model {
    protected $table='revenue_daily';
    protected $fillable=['day','downloads','impressions','ecpm','revenue_usd'];
    protected $casts=['day'=>'date'];
}

// app/Models/Payout.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model {
    protected $fillable=['provider','external_id','payout_date','amount_usd','status','meta'];
    protected $casts=['payout_date'=>'date','meta'=>'array'];
}

// app/Models/StripeAccount.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StripeAccount extends Model {
    protected $fillable=['user_id','account_id','account_type','status','capabilities'];
    protected $casts=['capabilities'=>'array'];
}
