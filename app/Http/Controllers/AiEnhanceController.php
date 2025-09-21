<?php

// app/Http/Controllers/AiEnhanceController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AiEnhanceController extends Controller
{
    public function enhance(Request $r)
    {
        $r->validate([
            'prompt' => ['required','string','max:5000'],
            'input'  => ['required','string','max:20000'],
        ]);

        $text = trim($r->input('input'));
        // Dumb heuristic “enhance” for now (keeps you moving)
        $text = preg_replace('/\s+/', ' ', $text);
        $text = rtrim($text, '.') . '.';
        $text .= " #PowerTime #Microsoft #Podcast";

        return response()->json(['text' => $text]);
    }
}
