<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EpisodeController extends Controller
{
    public function create()
    {
        return view('episodes.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $slug = $this->uniqueSlug($data['title']);

        $episode = Episode::create([
            'user_id'          => auth()->id(),
            'title'            => $data['title'],
            'slug'             => $slug,
            'description'      => $data['description'] ?? null,
            'audio_url'        => $data['audio_url'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'status'           => $data['status'],
            'published_at'     => $data['published_at'] ?? null,
        ]);

        return redirect()->route('episodes')->with('success', 'Episode created.');
    }
    

    public function edit(Episode $episode)
    {
        $this->authorizeOwnership($episode);
        return view('episodes.edit', compact('episode'));
    }

    public function update(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $data = $this->validated($request);

        // Keep slug stable unless title changed
        if ($episode->title !== $data['title']) {
            $episode->slug = $this->uniqueSlug($data['title'], $episode->id);
        }

        $episode->title            = $data['title'];
        $episode->description      = $data['description'] ?? null;
        $episode->audio_url        = $data['audio_url'] ?? null;
        $episode->duration_seconds = $data['duration_seconds'] ?? null;
        $episode->status           = $data['status'];
        $episode->published_at     = $data['published_at'] ?? null;
        $episode->save();

        return redirect()->route('episodes')->with('success', 'Episode updated.');
    }

    public function destroy(Episode $episode)
    {
        $this->authorizeOwnership($episode);
        $episode->delete();

        return redirect()->route('episodes')->with('success', 'Episode deleted.');
    }

    /** -------- Helpers -------- */

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'             => ['required','string','max:160'],
            'description'       => ['nullable','string'],
            'audio_url'         => ['nullable','url'],
            'duration_seconds'  => ['nullable','integer','min:0'],
            'status'            => ['required','in:draft,published'],
            'published_at'      => ['nullable','date'],
        ]);
    }

    

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'episode';
        $slug = $base; $i = 1;

        $query = Episode::query();
        if ($ignoreId) $query->where('id','!=',$ignoreId);

        while ($query->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    private function authorizeOwnership(Episode $episode): void
    {
        abort_unless($episode->user_id === auth()->id(), 403);
    }
}
