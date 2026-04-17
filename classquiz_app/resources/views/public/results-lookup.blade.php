<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Results — ClassQuiz</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:300,400,500,600|dm-serif-display:400&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 font-sans antialiased">

{{-- ─── Top nav ──────────────────────────────────────────────────────── --}}
<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur-sm">
    <div class="mx-auto flex h-14 max-w-4xl items-center justify-between px-5 sm:px-8">

        <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 shadow-sm
                        group-hover:bg-emerald-700 transition-colors duration-150">
                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.727 1.169 1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zm5.99 7.176A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
            </div>
            <span class="font-brand text-xl text-gray-900 tracking-tight leading-none">ClassQuiz</span>
        </a>

        <a href="{{ route('home') }}"
           class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 flex items-center gap-1.5">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to home
        </a>
    </div>
</header>

{{-- ─── Main ─────────────────────────────────────────────────────────── --}}
<main class="mx-auto max-w-lg px-5 sm:px-8 py-14">

    {{-- ── Status message (sent after form submit) ──────────── --}}
    @if(session('status'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-800">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    {{-- ── Rate-limit / validation errors ──────────────────────── --}}
    @if($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 text-sm text-red-700">
        <ul class="space-y-1 list-none m-0 p-0">
            @foreach($errors->all() as $error)
            <li class="flex items-start gap-2">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ $error }}
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ── Card ───────────────────────────────────────────────── --}}
    <div class="cq-card px-7 py-8">
        <div class="mb-6 flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h1 class="font-serif text-2xl text-gray-900 leading-tight">Find my results</h1>
                <p class="text-sm text-gray-500 mt-0.5">Enter the email you used when taking a quiz.</p>
            </div>
        </div>

        <p class="mb-5 text-sm text-gray-600 leading-relaxed">
            We'll email you a summary of the quizzes you've completed. Your results are
            sent privately — they won't be displayed here.
        </p>

        @if(!session('status'))
        <form method="POST" action="{{ route('taker.results.request') }}">
            @csrf

            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Email address
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="cq-field {{ $errors->has('email') ? 'cq-field-error' : '' }}"
                >
                @error('email')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="cq-btn-primary w-full justify-center">
                Email my results
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </button>
        </form>
        @endif
    </div>

    <p class="mt-6 text-center text-sm text-gray-400">
        Haven't taken a quiz yet?
        <a href="{{ route('home') }}" class="font-medium text-emerald-600 hover:text-emerald-700 hover:underline">
            Go back to the homepage
        </a> to find your quiz link.
    </p>

</main>
</body>
</html>
