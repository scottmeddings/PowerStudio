<?php
// app/Http/Controllers/EpisodeChapterController.php
namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\EpisodeChapter;
use Illuminate\Http\Request;

class EpisodeChapterController extends Controller
{
    public function index(Episode $episode)
    {
        $this->authorizeOwnership($episode);
        return response()->json($episode->chapters()->get(['id','title','starts_at_ms','sort']));
    }

    // Replace all chapters in one request
    public function sync(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $data = $request->validate([
            'chapters'   => ['required','array','max:200'],
            'chapters.*.title' => ['required','string','max:160'],
            'chapters.*.start' => ['required'], // accepts "mm:ss(.ms)" or seconds
        ]);

        // wipe & reinsert (simplest UX)
        $episode->chapters()->delete();

        $rows = [];
        foreach ($data['chapters'] as $i => $c) {
            $rows[] = new EpisodeChapter([
                'title'        => $c['title'],
                'starts_at_ms' => self::toMs($c['start']),
                'sort'         => $i,
            ]);
        }
        $episode->chapters()->saveMany($rows);

        return response()->json(['ok' => true]);
    }

    public function destroy(Episode $episode, EpisodeChapter $chapter)
    {
        $this->authorizeOwnership($episode);
        abort_unless($chapter->episode_id === $episode->id, 404);
        $chapter->delete();
        return response()->json(['ok'=>true]);
    }

    private static function toMs(string|int $v): int
    {
        if (is_numeric($v)) return (int)round(((float)$v) * 1000);
        // HH:MM:SS(.mmm)
        [$h,$m,$s] = array_pad(explode(':', $v), 3, '0');
        $ms = (float)$s * 1000 + ((int)$m)*60_000 + ((int)$h)*3_600_000;
        return (int)round($ms);
    }

    private function authorizeOwnership(Episode $episode): void
    {
        abort_unless($episode->user_id === auth()->id(), 403);
    }
}
