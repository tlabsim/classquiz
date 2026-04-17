<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ClassQuiz') }}</title>

    {{-- Fonts: DM Sans + DM Serif Display via Bunny Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:300,400,500,600|dm-serif-display:400&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 font-sans antialiased flex flex-col items-center justify-center px-4 py-16">

    {{-- Brand mark --}}
    <div class="mb-10 flex flex-col items-center gap-3">
        <a href="/" class="flex items-center gap-3 group">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-600 shadow-sm group-hover:bg-emerald-700 transition-colors duration-150">
                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.727 1.169 1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zm5.99 7.176A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
            </div>
            <span class="font-brand text-2xl text-gray-900 tracking-tight leading-none">ClassQuiz</span>
        </a>
    </div>

    {{-- Form card --}}
    <div class="w-full max-w-sm">
        <div class="cq-card px-8 py-8">
            {{ $slot }}
        </div>
    </div>

</body>
</html>
