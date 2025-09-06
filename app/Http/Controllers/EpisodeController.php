<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class EpisodeController extends Controller
{
    public function create()
    {
        return view('episodes.create');
    }

    /** Single source of truth for validation */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title'            => ['required','string','max:160'],
            'description'      => ['nullable','string'],
            'audio'            => ['nullable','file','mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/wave','max:512000'], // ~500MB
            'audio_url'        => ['nullable','url'],
            'duration_seconds' => ['nullable','integer','min:0'],
            'status'           => ['required','in:draft,published'],
            'published_at'     => ['nullable','date'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // Prefer uploaded file over URL
        $resolvedAudioUrl = $data['audio_url'] ?? null;
        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('audio', 'public');
            $resolvedAudioUrl = Storage::url($path); // /storage/audio/...
        }

        $slug = $this->uniqueSlug($data['title']);

        Episode::create([
            'user_id'          => auth()->id(),
            'title'            => $data['title'],
            'slug'             => $slug,
            'description'      => $data['description'] ?? null,
            'audio_url'        => $resolvedAudioUrl,
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

        // Slug only changes if title changes
        if ($episode->title !== $data['title']) {
            $episode->slug = $this->uniqueSlug($data['title'], $episode->id);
        }

        // Optional: manage published_at automatically if not provided
        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        if (($data['status'] ?? 'draft') !== 'published') {
            $data['published_at'] = null;
        }

        // Resolve audio: uploaded file wins; else keep URL or previous value
        $resolvedAudioUrl = $data['audio_url'] ?? $episode->audio_url;

        if ($request->hasFile('audio')) {
            // Remove old stored audio if it was on our public disk
            if ($episode->audio_url && str_starts_with($episode->audio_url, '/storage/')) {
                $old = str_replace('/storage/', '', $episode->audio_url);
                Storage::disk('public')->delete($old);
            }

            $newPath = $request->file('audio')->store('audio', 'public');
            $resolvedAudioUrl = Storage::url($newPath);
        }

        $episode->title            = $data['title'];
        $episode->description      = $data['description'] ?? null;
        $episode->audio_url        = $resolvedAudioUrl;
        $episode->duration_seconds = $data['duration_seconds'] ?? null;
        $episode->status           = $data['status'];
        $episode->published_at     = $data['published_at'] ?? null;
        $episode->save();

        return redirect()->route('episodes')->with('success', 'Episode updated.');
    }

    public function destroy(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        // Optional: delete stored audio on destroy
        if ($episode->audio_url && str_starts_with($episode->audio_url, '/storage/')) {
            $old = str_replace('/storage/', '', $episode->audio_url);
            Storage::disk('public')->delete($old);
        }

        $episode->delete();

        return redirect()->route('episodes')->with('success', 'Episode deleted.');
    }

    public function show(Episode $episode)
    {
        $episode->load(['comments' => fn($q) => $q->approved()->latest()->with('user:id,name')]);
        return view('pages.episode_show', compact('episode'));
    }

    /** Publish / Unpublish */
    public function publish(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if (strtolower($episode->status ?? 'draft') !== 'published') {
            $episode->forceFill([
                'status'       => 'published',
                'published_at' => now(),
            ])->save();
        }

        return back()->with('success', 'Episode published.');
    }

    public function unpublish(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if (strtolower($episode->status ?? '') === 'published') {
            $episode->forceFill([
                'status'       => 'draft',
                'published_at' => null,
            ])->save();
        }

        return back()->with('success', 'Episode unpublished.');
    }

    /** -------- Helpers -------- */

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'episode';
        $slug = $base; $i = 1;

        do {
            $exists = Episode::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
            if ($exists) $slug = $base.'-'.$i++;
        } while ($exists);

        return $slug;
    }

    private function authorizeOwnership(Episode $episode): void
    {
        abort_unless($episode->user_id === auth()->id(), 403);
    }

    /** Episode cover upload/remove */
    public function uploadCover(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $request->validate([
            'cover' => ['required','image','mimes:jpg,jpeg,png,webp','max:4096'],
        ]);

        if ($episode->cover_path) {
            Storage::disk('public')->delete($episode->cover_path);
        }

        $path = $request->file('cover')->store('covers/'.auth()->id(), 'public');

        $episode->update(['cover_path' => $path]);

        return back()->with('success', 'Episode cover updated.');
    }

    public function removeCover(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if ($episode->cover_path) {
            Storage::disk('public')->delete($episode->cover_path);
            $episode->update(['cover_path' => null]);
        }

        return back()->with('success', 'Episode cover removed (falling back to podcast cover).');
    }
}
