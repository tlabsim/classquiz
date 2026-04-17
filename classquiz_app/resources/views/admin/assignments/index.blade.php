@extends('layouts.admin')

@section('title', 'Assignments')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-40">{{ $quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700">Assignments</span>
@endsection

@section('content')
@php
    $activeCount = $assignments->where('is_active', true)->count();
    $sessionCount = $assignments->sum('sessions_count');
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="cq-badge-gray">{{ $quiz->title }}</span>
                <span class="cq-badge-green">{{ $activeCount }} active</span>
            </div>
            <h1 class="cq-page-title">Assignments</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                    {{ $assignments->count() }} {{ \Illuminate\Support\Str::plural('assignment', $assignments->count()) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                    {{ $sessionCount }} {{ \Illuminate\Support\Str::plural('session', $sessionCount) }}
                </span>
                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                    Showing newest first
                </span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.quizzes.show', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Quiz overview</a>
            <a href="{{ route('admin.quizzes.assignments.create', $quiz) }}" class="cq-btn-primary cq-btn-sm">New assignment</a>
        </div>
    </div>

    <div class="space-y-3">
        @forelse($assignments as $assignment)
            <div class="cq-card overflow-hidden" x-data="{ open: false }">
                <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span class="{{ $assignment->is_active ? 'cq-badge-green' : 'inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700' }}">
                                {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="cq-badge-blue">{{ $assignment->sessions_count }} {{ \Illuminate\Support\Str::plural('session', $assignment->sessions_count) }}</span>
                        </div>
                        <h2 class="text-base font-semibold text-gray-900">{{ $assignment->displayTitle() }}</h2>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                            <span>Created {{ $assignment->created_at->diffForHumans() }}</span>
                            <span>{{ $assignment->timezone() }}</span>
                            <span>{{ $assignment->setting('max_attempts', 1) }} {{ \Illuminate\Support\Str::plural('attempt', $assignment->setting('max_attempts', 1)) }}</span>
                            @if($assignment->duration_minutes)
                                <span>{{ $assignment->duration_minutes }} min</span>
                            @endif
                            <span>{{ $assignment->access_code_required ? 'Access code required' : 'Direct start' }}</span>
                            <span class="font-mono text-gray-600">{{ $assignment->public_token }}</span>
                            @if($assignment->is_active && ($assignment->availability_start || $assignment->availability_end))
                                <span>
                                    {{ $assignment->displayDateTime($assignment->availability_start, 'M j, g:i A') ?? 'Now' }}
                                    to
                                    {{ $assignment->displayDateTime($assignment->availability_end, 'M j, g:i A') ?? 'Open ended' }}
                                </span>
                            @elseif(!$assignment->is_active)
                                <span>Not currently active</span>
                            @endif
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                            <button type="button"
                                    data-copy-text="{{ $assignment->public_token }}"
                                    data-copy-label="Assignment code"
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700 transition-colors hover:bg-gray-200">
                                Copy code
                            </button>
                            <button type="button"
                                    data-copy-text="{{ route('quiz.show', $assignment->public_token) }}"
                                    data-copy-label="Public link"
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700 transition-colors hover:bg-gray-200">
                                Copy link
                            </button>
                            @if($assignment->sessions_count > 0)
                                <button type="button"
                                        @click="open = !open"
                                        class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700 transition-colors hover:bg-gray-200">
                                    <span x-text="open ? 'Hide recent sessions' : 'Show recent sessions'"></span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <a href="{{ route('admin.quizzes.assignments.show', [$quiz, $assignment]) }}" class="cq-btn-secondary cq-btn-sm">Overview</a>
                        <a href="{{ route('admin.quizzes.assignments.report', [$quiz, $assignment]) }}" class="cq-btn-secondary cq-btn-sm">Reports</a>
                        <a href="{{ route('admin.quizzes.assignments.edit', [$quiz, $assignment]) }}"
                           class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                           title="Edit assignment">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('admin.quizzes.assignments.destroy', [$quiz, $assignment]) }}"
                              onsubmit="return confirm('Delete this assignment?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500"
                                    title="Delete assignment">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                @if($assignment->sessions_count > 0)
                    <div x-cloak
                         x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="border-t border-gray-100 bg-gray-50/50 px-5 py-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Recent sessions</p>
                            <a href="{{ route('admin.quizzes.assignments.report', [$quiz, $assignment]) }}"
                               class="text-xs font-medium text-emerald-600 transition-colors hover:text-emerald-700">
                                View all sessions
                            </a>
                        </div>
                        <div class="space-y-2">
                            @foreach($assignment->sessions as $session)
                                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900">{{ $session->email }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ optional($session->submitted_at ?? $session->last_activity_at)->diffForHumans() }}</p>
                                    </div>
                                    <span class="cq-badge-gray">{{ ucfirst(str_replace('_', ' ', $session->status)) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="cq-card px-6 py-12 text-center">
                <p class="text-sm text-gray-500">No assignments yet. Create one when the quiz is ready to deliver.</p>
                <a href="{{ route('admin.quizzes.assignments.create', $quiz) }}" class="mt-4 inline-flex cq-btn-primary cq-btn-sm">Create assignment</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
