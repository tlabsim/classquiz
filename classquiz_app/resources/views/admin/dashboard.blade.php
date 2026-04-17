@extends('layouts.admin')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="text-gray-400">Dashboard</span>
@endsection

@section('content')

{{-- Page header --}}
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="cq-page-title">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">
            Welcome back, {{ auth()->user()->name }}
        </p>
    </div>
    <a href="{{ route('admin.quizzes.create') }}" class="cq-btn-primary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New quiz
    </a>
</div>

{{-- Stats cards --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">

    {{-- Total quizzes --}}
    <div class="cq-card px-6 py-5 flex items-start gap-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total quizzes</p>
            <p class="mt-0.5 text-3xl font-semibold text-gray-900 tabular-nums">{{ $stats['total_quizzes'] }}</p>
        </div>
    </div>

    {{-- Active assignments --}}
    <div class="cq-card px-6 py-5 flex items-start gap-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Assignments</p>
            <p class="mt-0.5 text-3xl font-semibold text-gray-900 tabular-nums">{{ $stats['total_assignments'] }}</p>
        </div>
    </div>

    {{-- Pending grading --}}
    <div class="cq-card px-6 py-5 flex items-start gap-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50">
            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Pending grading</p>
            <p class="mt-0.5 text-3xl font-semibold text-gray-900 tabular-nums">{{ $stats['pending_grading'] }}</p>
        </div>
    </div>

</div>

{{-- Recent sessions --}}
<div class="cq-card overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="cq-section-title">Recent sessions</h2>
        <a href="{{ route('admin.quizzes.index') }}"
           class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
            View all quizzes →
        </a>
    </div>

    @if($recentSessions->isEmpty())
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="mt-3 text-sm text-gray-500">No sessions yet.</p>
            <p class="text-xs text-gray-400 mt-1">Sessions will appear here once takers start quizzes.</p>
        </div>
    @else
        <div class="divide-y divide-gray-50">
            @foreach($recentSessions as $session)
            <div class="flex items-center justify-between px-6 py-3.5 hover:bg-gray-50/60 transition-colors">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                        {{ strtoupper(substr($session->email, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $session->email }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $session->assignment->quiz->title }}</p>
                    </div>
                </div>
                <div class="ml-4 shrink-0">
                    @php
                        $badgeClass = match($session->status) {
                            'graded'      => 'cq-badge-green',
                            'submitted'   => 'cq-badge-yellow',
                            'in_progress' => 'cq-badge-blue',
                            default       => 'cq-badge-gray',
                        };
                    @endphp
                    <span class="{{ $badgeClass }}">{{ str_replace('_', ' ', $session->status) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

@endsection

