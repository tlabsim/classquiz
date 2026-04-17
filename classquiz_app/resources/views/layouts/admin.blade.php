<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

    {{-- Fonts: DM Sans + DM Serif Display --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:300,400,500,600|dm-serif-display:400&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 font-sans antialiased" x-data="{ sidebarOpen: false }">

<div class="flex min-h-full">

    {{-- Mobile backdrop --}}
    <div x-cloak
         x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-20 bg-gray-900/40 backdrop-blur-sm lg:hidden">
    </div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col
                  bg-white border-r border-gray-200
                  -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out"
           :class="{ '!translate-x-0 shadow-xl lg:shadow-none': sidebarOpen }">

        {{-- Brand --}}
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-100 px-5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-600 shadow-sm">
                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.727 1.169 1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zm5.99 7.176A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
            </div>
            <span class="font-serif text-xl text-gray-900 tracking-tight">ClassQuiz</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5" aria-label="Sidebar">

            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-100
                      {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-emerald-600' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            {{-- Quizzes --}}
            <a href="{{ route('admin.quizzes.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-100
                      {{ request()->routeIs('admin.quizzes.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('admin.quizzes.*') ? 'text-emerald-600' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Quizzes
            </a>

            {{-- Assignments --}}
            @if(Route::has('admin.assignments.index'))
            <a href="{{ route('admin.assignments.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-100
                      {{ request()->routeIs('admin.assignments.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('admin.assignments.*') ? 'text-emerald-600' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Assignments
            </a>
            @endif

            {{-- Reports --}}
            @if(Route::has('admin.reports.index'))
            <a href="{{ route('admin.reports.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-100
                      {{ request()->routeIs('admin.reports.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('admin.reports.*') ? 'text-emerald-600' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Reports
            </a>
            @endif

            {{-- Import / Export (admin only) --}}
            @if(Route::has('admin.import') && auth()->user()?->role === 'admin')
            <a href="{{ route('admin.import') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-100
                      {{ request()->routeIs('admin.import*', 'admin.export*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('admin.import*', 'admin.export*') ? 'text-emerald-600' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                </svg>
                Import / Export
            </a>
            @endif

        </nav>

        {{-- User footer --}}
        <div class="shrink-0 border-t border-gray-100 p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-semibold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-gray-400">{{ ucfirst(auth()->user()->role) }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium
                               text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition-colors duration-100">
                    <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign out
                </button>
            </form>
        </div>

    </aside>
    {{-- ─── End sidebar ──────────────────────────────────────── --}}

    {{-- ─── Main content area ────────────────────────────────── --}}
    <div class="flex min-h-full flex-1 flex-col lg:pl-64">

        {{-- Top bar --}}
        <header class="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-4
                       border-b border-gray-200 bg-white/80 backdrop-blur-sm
                       px-4 sm:px-6 lg:px-8">
            {{-- Mobile hamburger --}}
            <button type="button"
                    @click="sidebarOpen = !sidebarOpen"
                    class="text-gray-500 hover:text-gray-700 transition-colors lg:hidden"
                    aria-label="Open navigation">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            {{-- Breadcrumb / contextual header --}}
            <div class="flex-1 text-sm text-gray-500">
                @yield('breadcrumb')
            </div>
        </header>

        {{-- Flash: success --}}
        @if(!View::hasSection('suppress_admin_alerts') && session('success'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 4000)"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="mx-4 sm:mx-6 lg:mx-8 mt-4 flex items-center gap-3 rounded-lg
                        border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Flash: error / validation --}}
        @if(!View::hasSection('suppress_admin_alerts') && (session('error') || $errors->any()))
            <div class="mx-4 sm:mx-6 lg:mx-8 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 space-y-0.5">
                @if(session('error'))
                    <p>{{ session('error') }}</p>
                @endif
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>

    </div>
    {{-- ─── End main ──────────────────────────────────────────── --}}

</div>
</body>
</html>
