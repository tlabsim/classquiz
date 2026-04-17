@extends('layouts.admin')

@section('title', 'Quizzes')

@section('breadcrumb')
    <span class="text-gray-400">Quizzes</span>
@endsection

@section('content')

{{-- Page header --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="cq-page-title">Quizzes</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $quizzes->total() }} {{ Str::plural('quiz', $quizzes->total()) }} total</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.import') }}" class="cq-btn-secondary cq-btn-sm">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M8 8a1 1 0 0 0 0 2h1a1 1 0 0 0 0-2Zm5 12H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5v3a3 3 0 0 0 3 3h3v2a1 1 0 0 0 2 0V8.94a1.3 1.3 0 0 0-.06-.27v-.09a1 1 0 0 0-.19-.28l-6-6a1 1 0 0 0-.28-.19a.3.3 0 0 0-.1 0a1.1 1.1 0 0 0-.31-.11H6a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h7a1 1 0 0 0 0-2m0-14.59L15.59 8H14a1 1 0 0 1-1-1ZM14 12H8a1 1 0 0 0 0 2h6a1 1 0 0 0 0-2m6.71 6.29a1 1 0 0 0-1.42 0l-.29.3V16a1 1 0 0 0-2 0v2.59l-.29-.3a1 1 0 0 0-1.42 1.42l2 2a1 1 0 0 0 .33.21a.94.94 0 0 0 .76 0a1 1 0 0 0 .33-.21l2-2a1 1 0 0 0 0-1.42M12 18a1 1 0 0 0 0-2H8a1 1 0 0 0 0 2Z"/>
            </svg>
            Import JSON
        </a>
        <a href="{{ route('admin.quizzes.create') }}" class="cq-btn-primary cq-btn-sm">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New quiz
        </a>
    </div>
</div>

{{-- Search + sort toolbar --}}
<form method="GET" action="{{ route('admin.quizzes.index') }}"
      class="mb-5 grid gap-3 lg:grid-cols-3">
    <div class="relative min-w-0 lg:col-span-2">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
        </svg>
        <input type="text" name="search" value="{{ $search }}"
               placeholder="Search quizzes..."
               class="cq-field pl-9 py-2 text-sm w-full">
    </div>
    <select name="sort" onchange="this.form.submit()"
            class="cq-field min-w-0 w-full py-2 pr-8 text-sm">
        <option value="newest"     {{ $sort === 'newest'     ? 'selected' : '' }}>Newest first</option>
        <option value="oldest"     {{ $sort === 'oldest'     ? 'selected' : '' }}>Oldest first</option>
        <option value="title_asc"  {{ $sort === 'title_asc'  ? 'selected' : '' }}>Title A&ndash;Z</option>
        <option value="title_desc" {{ $sort === 'title_desc' ? 'selected' : '' }}>Title Z&ndash;A</option>
        <option value="questions"  {{ $sort === 'questions'  ? 'selected' : '' }}>Most questions</option>
        <option value="assignments_recent" {{ $sort === 'assignments_recent' ? 'selected' : '' }}>Recent assignments</option>
        <option value="assignments_oldest" {{ $sort === 'assignments_oldest' ? 'selected' : '' }}>Old assignments</option>
    </select>
    @if($search)
    <a href="{{ route('admin.quizzes.index') }}"
       class="text-sm text-gray-400 transition-colors hover:text-gray-600 lg:col-span-3">
        Clear
    </a>
    @endif
</form>

{{-- Quiz list --}}
<div class="space-y-2">
    @forelse($quizzes as $quiz)
    <div class="cq-card overflow-hidden" x-data="{ open: false }">

        {{-- Main row --}}
        <div class="flex items-center gap-4 px-5 py-4">
            {{-- Expand toggle --}}
            <button type="button" @click="open = !open"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-gray-400
                           hover:bg-gray-100 hover:text-gray-600 transition-colors"
                    :aria-expanded="open" aria-label="Toggle details">
                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-90': open }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            {{-- Quiz icon --}}
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>

            {{-- Title + meta --}}
            <div class="min-w-0 flex-1">
                <a href="{{ route('admin.quizzes.show', $quiz) }}"
                   class="block truncate font-semibold text-gray-900 transition-colors hover:text-emerald-700">
                    {{ $quiz->title }}
                </a>
                <p class="text-xs text-gray-400 mt-0.5">
                    by {{ $quiz->creator->name }}
                    &middot; {{ $quiz->questions_count }} {{ Str::plural('question', $quiz->questions_count) }}
                    &middot; {{ $quiz->created_at->diffForHumans() }}
                </p>
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center gap-1 shrink-0">
                <a href="{{ route('admin.quizzes.show', $quiz) }}"
                   class="cq-btn-secondary cq-btn-sm hidden sm:inline-flex">
                    Overview
                </a>
                <a href="{{ route('admin.quizzes.questions.index', $quiz) }}"
                   class="cq-btn-secondary cq-btn-sm hidden sm:inline-flex">
                    Questions
                    <span class="ml-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-gray-200 px-1.5 py-0.5 text-[11px] font-semibold text-gray-700">
                        {{ $quiz->questions_count }}
                    </span>
                </a>
                <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}"
                   class="cq-btn-secondary cq-btn-sm hidden sm:inline-flex">
                    Assignments
                    <span class="ml-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-gray-200 px-1.5 py-0.5 text-[11px] font-semibold text-gray-700">
                        {{ $quiz->assignments_count }}
                    </span>
                </a>
                <a href="{{ route('admin.quizzes.edit', $quiz) }}"
                   class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                   title="Edit quiz">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
                <a href="{{ route('admin.quizzes.export', $quiz) }}"
                   class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                   title="Export quiz">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M20.92 15.62a1.2 1.2 0 0 0-.21-.33l-3-3a1 1 0 0 0-1.42 1.42l1.3 1.29H12a1 1 0 0 0 0 2h5.59l-1.3 1.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l3-3a.9.9 0 0 0 .21-.33a1 1 0 0 0 0-.76M14 20H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5v3a3 3 0 0 0 3 3h4a1 1 0 0 0 .92-.62a1 1 0 0 0-.21-1.09l-6-6a1 1 0 0 0-.28-.19h-.09l-.28-.1H6a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h8a1 1 0 0 0 0-2M13 5.41L15.59 8H14a1 1 0 0 1-1-1Z"/>
                    </svg>
                </a>
                <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}"
                      x-data
                      @submit.prevent="if(confirm('Delete &quot;{{ addslashes($quiz->title) }}&quot;? This cannot be undone.')) $el.submit()">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors"
                            title="Delete quiz">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Collapsible details panel --}}
        <div x-cloak x-show="open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="border-t border-gray-100 bg-gray-50/50 px-5 py-4">

            {{-- Mobile nav shortcuts --}}
            <div class="flex gap-2 mb-4 sm:hidden">
                <a href="{{ route('admin.quizzes.show', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Overview</a>
                <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Questions <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-gray-200 px-1.5 py-0.5 text-[11px] font-semibold text-gray-700">{{ $quiz->questions_count }}</span></a>
                <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Assignments <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-gray-200 px-1.5 py-0.5 text-[11px] font-semibold text-gray-700">{{ $quiz->assignments_count }}</span></a>
            </div>

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Recent assignments</p>

            @if($quiz->assignments->isEmpty())
                <p class="text-sm text-gray-400 italic">No assignments yet.
                    <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="text-emerald-600 hover:underline not-italic">Create one &rarr;</a>
                </p>
            @else
                <div class="space-y-2">
                    @foreach($quiz->assignments->take(3) as $assignment)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $assignment->displayTitle() }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $assignment->sessions_count }} {{ Str::plural('session', $assignment->sessions_count) }}
                                &middot;
                                <span class="{{ $assignment->is_active ? 'text-emerald-600' : 'text-gray-400' }}">
                                    {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-4">
                            <a href="{{ route('admin.quizzes.assignments.report', [$quiz, $assignment]) }}"
                               class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                                Results
                            </a>
                            <a href="{{ route('admin.quizzes.assignments.show', [$quiz, $assignment]) }}"
                               class="text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                Manage
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($quiz->assignments->count() > 0)
                <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}"
                   class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                    View all assignments
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endif
            @endif
        </div>

    </div>
    @empty
    <div class="cq-card px-6 py-12 text-center">
        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        @if($search)
            <p class="mt-3 text-sm text-gray-500">No quizzes match <strong>"{{ $search }}"</strong>.</p>
            <a href="{{ route('admin.quizzes.index') }}" class="mt-2 inline-block text-sm text-emerald-600 hover:underline">Clear search</a>
        @else
            <p class="mt-3 text-sm text-gray-500">No quizzes yet.</p>
            <a href="{{ route('admin.quizzes.create') }}" class="mt-4 inline-flex cq-btn-primary cq-btn-sm">Create your first quiz</a>
        @endif
    </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($quizzes->hasPages())
<div class="mt-6">{{ $quizzes->links() }}</div>
@endif

@endsection
