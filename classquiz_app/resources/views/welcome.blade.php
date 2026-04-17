<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ClassQuiz</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:300,400,500,600|dm-serif-display:400&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 font-sans antialiased">

{{-- â”€â”€â”€ Top nav â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur-sm">
    <div class="mx-auto flex h-14 max-w-4xl items-center justify-between px-5 sm:px-8">

        <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 shadow-sm
                        group-hover:bg-emerald-700 transition-colors duration-150">
                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.727 1.169 1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zm5.99 7.176A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
            </div>
            <span class="font-serif text-xl text-gray-900 tracking-tight leading-none">ClassQuiz</span>
        </a>

        <nav class="flex items-center gap-2">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="cq-btn-primary cq-btn-sm">
                    Dashboard
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100">
                    Teacher sign in
                </a>
            @endauth
        </nav>
    </div>
</header>

{{-- â”€â”€â”€ Main â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<main class="mx-auto max-w-4xl px-5 sm:px-8 py-14">

    <div class="mb-10 text-center">
        <h1 class="font-serif text-3xl sm:text-4xl text-gray-900 tracking-tight">Welcome to ClassQuiz</h1>
        <p class="mt-3 text-gray-500 text-base max-w-lg mx-auto">An internal quiz platform for teachers and students. What would you like to do?</p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">

        {{-- â”€â”€ 1. Take a quiz â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="cq-card px-6 py-7 sm:col-span-2"
             x-data="{
                quizInput: '',
                error: '',
                submit() {
                    this.error = '';
                    const raw = this.quizInput.trim();
                    if (!raw) { this.error = 'Please enter a quiz link or code.'; return; }
                    const match = raw.match(/\/q\/([A-Za-z0-9_-]+)/);
                    const token = match ? match[1] : raw;
                    if (!/^[A-Za-z0-9_-]{4,}$/.test(token)) {
                        this.error = 'That doesn\'t look like a valid quiz link or code.';
                        return;
                    }
                    window.location.href = '/q/' + token;
                }
             }">

            <div class="flex items-center gap-3 mb-1">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </div>
                <h2 class="font-semibold text-gray-900">Take a quiz</h2>
            </div>
            <p class="text-sm text-gray-500 mb-5 pl-12">
                Your teacher will share a link or code. Paste it below to begin.
            </p>

            <form @submit.prevent="submit" novalidate class="pl-12">
                <div class="flex gap-2">
                    <input
                        id="quiz-link"
                        type="text"
                        x-model="quizInput"
                        placeholder="Paste your quiz link or code here"
                        autocomplete="off"
                        class="cq-field flex-1"
                        :class="{ 'cq-field-error': error }"
                    />
                    <button type="submit" class="cq-btn-primary shrink-0">
                        Open
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
                <p x-cloak x-show="error" x-text="error" class="mt-2 text-xs text-red-600"></p>
            </form>

            <div class="mt-6 pl-12 pt-5 border-t border-gray-100">
                <p class="text-xs text-gray-400 mb-1 font-medium uppercase tracking-wide">How it works</p>
                <ol class="space-y-1.5 text-sm text-gray-500 list-decimal list-inside">
                    <li>Open the quiz link shared by your teacher</li>
                    <li>Enter your email &mdash; you'll receive a one-time access code</li>
                    <li>Enter the code and start the quiz</li>
                </ol>
            </div>
        </div>

        {{-- â”€â”€ Right column â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="flex flex-col gap-5">

            {{-- 2. View my results --}}
            <div class="cq-card px-6 py-6 flex flex-col gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h2 class="font-semibold text-gray-900">My results</h2>
                </div>
                <p class="text-sm text-gray-500">
                    Took a quiz before? Enter your email to receive a summary of your results.
                </p>
                <a href="{{ route('taker.results') }}" class="cq-btn-secondary w-full justify-center text-sm mt-1">
                    View my results
                </a>
            </div>

            {{-- 3. Teacher access --}}
            <div class="cq-card px-6 py-6 flex flex-col gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100">
                        <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="font-semibold text-gray-900">Teachers</h2>
                </div>
                <p class="text-sm text-gray-500">
                    Access the admin dashboard to create and manage quizzes.
                </p>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="cq-btn-primary w-full justify-center text-sm mt-1">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="cq-btn-secondary w-full justify-center text-sm mt-1">Sign in</a>
                @endauth
            </div>

        </div>
    </div>

</main>

<footer class="border-t border-gray-200 mt-8">
    <div class="mx-auto max-w-4xl px-5 sm:px-8 py-5 text-xs text-gray-400 text-center">
        &copy; {{ date('Y') }} ClassQuiz &mdash; Internal use only
    </div>
</footer>

</body>
</html>
