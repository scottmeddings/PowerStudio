<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;           // ✅ add this
use App\Models\EpisodeTranscript;
use Illuminate\Support\Facades\Log;


class EpisodeTranscriptController extends Controller
{
public function show($episodeId)
    {
        $id = (int) $episodeId;

        // Debug log so we can see exactly what runs.
        Log::info('Transcript.show', [
            'episode_id' => $id,
            'count'      => EpisodeTranscript::where('episode_id', $id)->count(),
        ]);

        // Read directly from the table (bypass relation/model binding entirely)
        $tr = EpisodeTranscript::where('episode_id', $id)->first();

        if (!$tr) {
            return response()->json(['ok' => false, 'message' => 'No transcript row']);
        }

        // Prefer DB body; fallback to file only if body empty
        $raw = (string) ($tr->body ?? '');
        if ($raw === '' && $tr->storage_path && Storage::disk('public')->exists($tr->storage_path)) {
            $raw = (string) Storage::disk('public')->get($tr->storage_path);
        }

        $body = $this->utf8Clean($raw);

        if ($body === '') {
            return response()->json(['ok' => false, 'message' => 'No transcript body']);
        }

        return response()->json([
            'ok'          => true,
            'format'      => $tr->format ?? 'txt',
            'duration_ms' => $tr->duration_ms,
            'body'        => $body,
            'updated_at'  => optional($tr->updated_at)->toDateTimeString(),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function utf8Clean(string $s): string
    {
        if ($s === '') return '';
        // Strip BOM if already UTF-8
        if (mb_detect_encoding($s, 'UTF-8', true)) {
            return preg_replace('/^\xEF\xBB\xBF/u', '', $s) ?? $s;
        }
        foreach (['UTF-16LE','UTF-16BE','Windows-1252','ISO-8859-1'] as $enc) {
            $out = @mb_convert_encoding($s, 'UTF-8', $enc);
            if ($out !== false && mb_detect_encoding($out, 'UTF-8', true)) {
                return preg_replace('/^\xEF\xBB\xBF/u', '', $out) ?? $out;
            }
        }
        $out = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        return is_string($out) ? $out : '';
    }


    public function store(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $data = $request->validate([
            'text'        => ['nullable', 'string'],
            // Prefer "mimes" over "mimetypes" for reliability
            'file'        => ['nullable', 'file', 'mimes:vtt,srt,txt', 'max:10240'], // 10MB
            'format'      => ['nullable', Rule::in(['vtt','srt','txt'])],
            'duration_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        if (empty($data['text']) && !$request->hasFile('file')) {
            return $this->backOrJson($request, ['text' => 'Provide a transcript file or paste text.'], 422);
        }

        // Decide final format
        $format = $data['format'] ?? 'vtt';
        $path   = null;
        $body   = $data['text'] ?? null;

        if ($request->hasFile('file')) {
            // Derive extension from the upload
            $ext = strtolower($request->file('file')->getClientOriginalExtension());
            if (!in_array($ext, ['vtt', 'srt', 'txt'], true)) {
                // fallback to requested format or vtt
                $ext = $format ?: 'vtt';
            }

            // Store with deterministic name to keep things tidy
            $filename = 'transcript-' . now()->format('YmdHis') . '.' . $ext;
            $path = $request->file('file')->storeAs(
                "transcripts/{$episode->id}",
                $filename,
                'public'
            );

            // If no inline text provided, read the stored file into body
            if (!$body) {
                $body = Storage::disk('public')->get($path);
            }

            $format = $ext; // align format to what we stored
        }

        // Upsert the single transcript row (hasOne) for this episode
        $episode->transcript()
            ->updateOrCreate(
                ['episode_id' => $episode->id], // match on FK (prevents dupes)
                [
                    'format'       => $format,
                    'body'         => $body,
                    'storage_path' => $path,
                    'duration_ms'  => $data['duration_ms'] ?? null,
                ]
            );

        return $this->okOrRedirect($request, 'Transcript saved.');
    }

    public function destroy(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if ($tr = $episode->transcript) {
            if ($tr->storage_path) {
                Storage::disk('public')->delete($tr->storage_path);
            }
            $tr->delete();
        }

        return response()->json(['ok' => true]);
    }

    public function download(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $tr = $episode->transcript;
        abort_unless($tr && $tr->storage_path, 404);

        // Friendly filename
        $base = str($episode->title ?: "episode-{$episode->id}")
            ->slug('-')
            ->limit(60, '')
            ->value();

        $downloadAs = "{$base}.{$tr->format}";

        return Storage::disk('public')->download($tr->storage_path, $downloadAs);
    }

    private function authorizeOwnership(Episode $episode): void
    {
        abort_unless($episode->user_id === auth()->id(), 403);
    }

    private function backOrJson(Request $request, array $errors, int $status)
    {
        if ($request->wantsJson()) {
            return response()->json(['ok' => false, 'errors' => $errors], $status);
        }
        return back()->withErrors($errors);
    }

    private function okOrRedirect(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }
    
}
