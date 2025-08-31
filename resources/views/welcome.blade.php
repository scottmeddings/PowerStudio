{{-- resources/views/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-gray-100 dark:bg-gray-900">
        <div class="flex flex-col items-center justify-center min-h-screen">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                Welcome to {{ config('app.name', 'Laravel') }}
            </h1>

            {{-- Social login buttons --}}
            <div class="w-64">
                <a class="btn w-full" href="{{ route('social.redirect', 'google') }}">
                    Continue with Google
                </a>
                <a class="btn w-full mt-2" href="{{ route('social.redirect', 'microsoft') }}">
                    Continue with Microsoft
                </a>
            </div>
        </div>
    </body>
</html>
