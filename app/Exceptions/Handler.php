<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Friendly message when uploads exceed PHP/post size limits
        $this->renderable(function (PostTooLargeException $e, Request $request) {
            // If an API request, return JSON with a 413 status
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The uploaded data exceeds the server limit.',
                    'max'     => ini_get('post_max_size'), // e.g. "2048M"
                ], 413);
            }

            // For browser forms, go back with an error on the "audio" field
            return back()
                ->withInput()
                ->withErrors([
                    'audio' => 'The file is larger than the server allows. Max is 2 GB.',
                ]);
        });
    }
}
