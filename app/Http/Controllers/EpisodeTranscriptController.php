<?php

// app/Http/Controllers/EpisodeTranscriptController.php
namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\EpisodeTranscript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EpisodeTranscriptController extends Controller
{
    public function show(Episode $episode)
    {
        $this->authorizeOwnership($episode);
        return response()->json($episode->transcript);
    }

    public function store(Request $request, Episode $episode)
    {
        $this->authorizeOwnership($episode);

        $data = $request->validate([
            'text'   => ['nullable','string'],
            'file'   => ['nullable','file','mimetypes:text/plain,text/vtt,application/x-subrip','max:10240'],
            'format' => ['nullable','in:vtt,srt,txt'],
            'duration_ms' => ['nullable','integer','min:0'],
        ]);

        if (empty($data['text']) && !$request->hasFile('file')) {
            return back()->withErrors(['text' => 'Provide a transcript file or paste text.']);
        }

        $format = $data['format'] ?? 'vtt';
        $path = null; $body = $data['text'] ?? null;

        if ($request->hasFile('file')) {
            $ext = match($format){ 'srt'=>'srt', 'txt'=>'txt', default=>'vtt' };
            $path = $request->file('file')->store("transcripts/{$episode->id}", 'public');
            $format = $ext; // best effort
            if (!$body) $body = file_get_contents(Storage::disk('public')->path($path));
        }

        $episode->transcript()
            ->updateOrCreate([], [
                'format'       => $format,
                'body'         => $body,
                'storage_path' => $path,
                'duration_ms'  => $data['duration_ms'] ?? null,
            ]);

        return response()->json(['ok'=>true]);
    }

    public function destroy(Episode $episode)
    {
        $this->authorizeOwnership($episode);

        if ($tr = $episode->transcript) {
            if ($tr->storage_path) Storage::disk('public')->delete($tr->storage_path);
            $tr->delete();
        }
        return response()->json(['ok'=>true]);
    }

    public function download(Episode $episode)
    {
        $this->authorizeOwnership($episode);
        $tr = $episode->transcript;
        abort_unless($tr && $tr->storage_path, 404);
        return Storage::disk('public')->download($tr->storage_path);
    }

    private function authorizeOwnership(Episode $episode): void
    {
        abort_unless($episode->user_id === auth()->id(), 403);
    }
}
