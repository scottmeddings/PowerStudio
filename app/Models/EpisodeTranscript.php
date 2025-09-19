<?php
// app/Models/EpisodeTranscript.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeTranscript extends \App\Models\TenantModel
{
    protected $fillable = ['episode_id','format','body','duration_ms','storage_path'];

    public function episode() { return $this->belongsTo(Episode::class); }

  
}
