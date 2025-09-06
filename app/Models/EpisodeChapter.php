<?php
// app/Models/EpisodeChapter.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeChapter extends Model
{
    protected $fillable = ['episode_id','starts_at_ms','title','sort'];
    public function episode(){ return $this->belongsTo(Episode::class); }
}
