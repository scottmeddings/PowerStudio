<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\EpisodeTranscript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EpisodeTranscriptController extends Controller
{
    /**
     * Store (upload or paste) transcript.
     */

    public function show(Episode $episode)
    {
        $tr = \DB::table('episode_transcripts')
            ->where('episode_id', $episode->id)
            ->first();

        return response()->json([
            'ok'          => true,
            'body'        => $tr?->body ?? '',
            'format'      => $tr?->format ?? 'TXT',
            'duration_ms' => $tr?->duration_ms,
        ]);
    }

public function store(Request $request, Episode $episode)
{
    $this->authorizeOwnership($episode);

    $request->validate([
        'file' => ['nullable', 'file', 'mimes:vtt,srt,txt'],
        'text' => ['nullable', 'string'],
    ]);

    $content = '';
    $format  = 'TXT';

    if ($request->hasFile('file')) {
        $content = file_get_contents($request->file('file')->getRealPath());
        $ext     = $request->file('file')->getClientOriginalExtension();
        $format  = strtoupper($ext);
    } else {
        $content = $request->input('text', '');
    }

    $episode->transcript()->updateOrCreate(
        ['episode_id' => $episode->id],
        [
            'body'        => $content,
            'format'      => $format,
            'duration_ms' => $episode->duration_seconds
                ? $episode->duration_seconds * 1000
                : null,
        ]
    );

    return response()->json(['ok' => true, 'msg' => 'Transcript saved.']);
}

public function destroy(Episode $episode)
{
    $this->authorizeOwnership($episode);
    $episode->transcript()?->delete();

    return response()->json(['ok' => true, 'msg' => 'Transcript deleted.']);
}


    /**
     * Download transcript as file.
     */
    public function download(Episode $episode): StreamedResponse
    {
        $this->authorizeOwnership($episode);

        $tr = $episode->transcript;

        if (!$tr) {
            abort(404, 'Transcript not found');
        }

        $ext = strtolower($tr->format ?? 'txt');
        $filename = "episode-{$episode->id}-transcript.{$ext}";

        return response()->streamDownload(function () use ($tr) {
            echo $tr->body;
        }, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
