<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;


class EpisodeController extends Controller
{
    public function create()
    {
        return view('episodes.create');
    }

    /** Single source of truth for validation */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'title'            => ['required', 'string', 'max:160'],
            'description'      => ['nullable', 'string'],
            'audio'            => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav', 'max:2097152'], // 2GB in KB
            'audio_url'        => ['nullable', 'url'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status'           => ['required', 'in:draft,published'],
            'published_at'     => ['nullable', 'date'],
            'cover'            => ['nullable', 'image'],
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
        dispatch(new \App\Jobs\ShareEpisodeToSocials($episode));
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


    public function aiEnhance(Request $request, \App\Models\Episode $episode)
    {
        $this->authorize('update', $episode);

        // 1) Find a local temp file for the audio
        //    - If audio_url points to /storage/..., map to local storage path
        //    - If remote URL, download to a temp file
        $source = $episode->audio_url;
        if (! $source) {
            return back()->withErrors(['audio_url' => 'This episode has no audio URL to analyze.']);
        }

        $localPath = null;

        try {
            if (Str::startsWith($source, url('/storage'))) {
                // Map a public storage URL -> local path (storage/app/public/...)
                $relative = Str::after($source, url('/storage/'));
                $localPath = storage_path('app/public/'.$relative);
                if (! is_file($localPath)) {
                    return back()->withErrors(['audio_url' => 'Could not read local audio file.']);
                }
            } elseif (Str::startsWith($source, ['http://', 'https://'])) {
                // Remote URL: download to temp (OpenAI requires a file upload)
                $tmp = tmpfile();
                $tmpMeta = stream_get_meta_data($tmp);
                $localPath = $tmpMeta['uri'];

                $response = Http::timeout(120)->get($source);
                if (! $response->ok()) {
                    return back()->withErrors(['audio_url' => 'Could not download the audio from the URL.']);
                }
                file_put_contents($localPath, $response->body());
            } else {
                // Maybe it's already a relative storage path
                $maybe = storage_path('app/'.$source);
                if (is_file($maybe)) {
                    $localPath = $maybe;
                } else {
                    return back()->withErrors(['audio_url' => 'Unsupported audio location.']);
                }
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['audio_url' => 'Audio retrieval failed: '.$e->getMessage()]);
        }

        // OpenAI key
        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            return back()->withErrors(['ai' => 'Missing OPENAI_API_KEY. Add it to .env and config/services.php.']);
        }

        try {
                // 2) Transcribe with Whisper
                //    Endpoint: POST /v1/audio/transcriptions (multipart)
                $transcription = Http::asMultipart()
                    ->withToken($apiKey)
                    ->attach('file', fopen($localPath, 'r'), basename($localPath))
                    ->post('https://api.openai.com/v1/audio/transcriptions', [
                        'model' => 'whisper-1',            // or 'gpt-4o-mini-transcribe' if available to you
                        'response_format' => 'text',       // keep it simple; returns plain text
                        // 'temperature' => 0.2,
                    ])->throw()->body(); // plain text transcript

                // 3) Ask a chat model to summarize into JSON (title/description/chapters)
                $prompt = <<<PROMPT
        You are an editorial assistant for podcast show notes.
        Given the raw transcript below, produce a compact JSON object with:
        - "title": a compelling 60-90 character title (no quotes inside).
        - "description": 2-4 sentence paragraph summary for the episode (plain text).
        - "chapters": an array of objects with "start" (HH:MM:SS) and "title". Create 5-12 chapters. If you cannot infer times, set "start" to "00:00:00", "00:05:00", etc. spaced evenly.

        Return ONLY valid JSON. Do not wrap it in code fences.

        TRANSCRIPT:
        {$transcription}
        PROMPT;

                $chat = Http::withToken($apiKey)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',  // or 'gpt-4.1-mini' / any small capable model
                        'temperature' => 0.3,
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            ['role' => 'system', 'content' => 'You write excellent podcast metadata.'],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ])->throw()->json();

                $json = json_decode($chat['choices'][0]['message']['content'] ?? '{}', true);

                // Basic guards
                if (!is_array($json)) $json = [];
                $newTitle   = $json['title'] ?? null;
                $newDesc    = $json['description'] ?? null;
                $newChaps   = $json['chapters'] ?? null;

                // 4) Save to the episode (requires ai columns; migration below)
                $episode->title       = $newTitle ?: ($episode->title ?: 'Untitled');
                if ($newDesc) $episode->description = $newDesc;
                if (is_array($newChaps)) $episode->chapters = $newChaps;       // json column
                $episode->transcript  = $transcription;                         // long text
                $episode->save();

                return back()->with('success', 'AI enhancement complete — title, description, chapters, and transcript updated.');
            } catch (\Illuminate\Http\Client\RequestException $e) {
                $msg = $e->response?->json('error.message') ?? $e->getMessage();
                return back()->withErrors(['ai' => 'OpenAI error: '.$msg]);
            } catch (\Throwable $e) {
                return back()->withErrors(['ai' => 'AI enhancement failed: '.$e->getMessage()]);
            }
        }

}
