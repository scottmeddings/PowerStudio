<?php
// app/Models/EpisodeTranscript.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeTranscript extends Model
{
    protected $fillable = ['episode_id','format','body','storage_path','duration_ms'];
    public function episode(){ return $this->belongsTo(Episode::class); }
}
