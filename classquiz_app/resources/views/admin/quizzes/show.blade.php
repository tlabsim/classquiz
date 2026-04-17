@extends('layouts.admin')

@section('title', $quiz->title)

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700 truncate max-w-52">{{ $quiz->title }}</span>
@endsection

@section('content')
@php
    $totalPoints = rtrim(rtrim(number_format($stats['question_points'], 2), '0'), '.');
    $enabledPoints = rtrim(rtrim(number_format($stats['enabled_question_points'], 2), '0'), '.');
    $disabledQuestionCount = $stats['question_count'] - $stats['enabled_question_count'];
    $disabledPoints = rtrim(rtrim(number_format($stats['question_points'] - $stats['enabled_question_points'], 2), '0'), '.');
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="cq-badge-gray">Quiz</span>
                <span class="cq-badge-green">{{ $stats['active_assignment_count'] }} active assignments</span>
            </div>
            <h1 class="cq-page-title">{{ $quiz->title }}</h1>
            @if($quiz->description)
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">{{ $quiz->description }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="cq-btn-primary cq-btn-sm">Add question</a>
            <a href="{{ route('admin.quizzes.assignments.create', $quiz) }}" class="cq-btn-secondary cq-btn-sm">New assignment</a>
            <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Quiz settings</a>
            <a href="{{ route('admin.quizzes.export', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Export quiz</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="cq-card px-5 py-4 sm:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Questions</p>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['question_count'] }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $totalPoints }} pts total</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="cq-badge-green">{{ $stats['enabled_question_count'] }} enabled</span>
                    <span class="cq-badge-gray">{{ $disabledQuestionCount }} disabled</span>
                    <span class="cq-badge-blue">{{ $enabledPoints }} pts live</span>
                    @if($disabledQuestionCount > 0)
                        <span class="cq-badge-gray">{{ $disabledPoints }} pts hidden</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="cq-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Assignments</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['assignment_count'] }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ $stats['active_assignment_count'] }} active now</p>
        </div>
        <div class="cq-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sessions</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['session_count'] }}</p>
            <p class="mt-1 text-sm text-gray-500">Across all assignments</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="cq-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 class="cq-section-title">Question Summary</h2>
                    <p class="mt-1 text-sm text-gray-500">See how the quiz is currently composed.</p>
                </div>
                <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Manage questions</a>
            </div>
            <div class="px-6 py-5">
                @if($quiz->questions->isEmpty())
                    <p class="text-sm text-gray-500">No questions yet. Add the first question to start building the quiz.</p>
                @else
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-gray-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Single correct</p>
                            <p class="mt-2 text-xl font-semibold text-gray-900">{{ $quiz->questions->where('type', 'mcq_single')->count() }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Multi correct</p>
                            <p class="mt-2 text-xl font-semibold text-gray-900">{{ $quiz->questions->where('type', 'mcq_multi')->count() }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">True / False</p>
                            <p class="mt-2 text-xl font-semibold text-gray-900">{{ $quiz->questions->where('type', 'tf')->count() }}</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2">
                        @foreach($quiz->questions->take(5) as $question)
                            <div class="flex items-start justify-between gap-4 rounded-xl border border-gray-100 px-4 py-3">
                                <div class="min-w-0">
                                    <div class="mb-1 flex flex-wrap items-center gap-2">
                                        <span class="cq-badge-blue">{{ $question->type === 'mcq_single' ? 'MCQ-Single' : ($question->type === 'mcq_multi' ? 'MCQ-Multi' : 'True / False') }}</span>
                                        @if($question->tag)
                                            <span class="cq-badge-gray">{{ $question->tag }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm leading-6 text-gray-800">{{ \Illuminate\Support\Str::limit(strip_tags($question->text), 140, '...') }}</p>
                                </div>
                                <span class="text-xs text-gray-400">{{ rtrim(rtrim(number_format($question->points, 2), '0'), '.') }} pts</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="cq-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <h2 class="cq-section-title">Latest Assignments</h2>
                        <p class="mt-1 text-sm text-gray-500">Recent launches and linked student activity.</p>
                    </div>
                    <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="cq-btn-secondary cq-btn-sm">View all</a>
                </div>
                <div class="px-6 py-5">
                    @if($assignments->isEmpty())
                        <p class="text-sm text-gray-500">No assignments yet. Create one when the quiz is ready to deliver.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($assignments as $assignment)
                                <a href="{{ route('admin.quizzes.assignments.show', [$quiz, $assignment]) }}"
                                   class="block rounded-xl border border-gray-100 px-4 py-3 transition-colors hover:border-emerald-200 hover:bg-emerald-50/40">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                                <span class="{{ $assignment->is_active ? 'cq-badge-green' : 'cq-badge-gray' }}">
                                                    {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                                <span class="text-xs text-gray-400">{{ $assignment->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="truncate text-sm font-medium text-gray-900">{{ $assignment->displayTitle() }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $assignment->sessions_count }} {{ \Illuminate\Support\Str::plural('session', $assignment->sessions_count) }}</p>
                                        </div>
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="cq-card px-6 py-5">
                <h2 class="cq-section-title">Portable Export</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">Download the portable quiz package with quiz metadata, questions, choices, and settings. Use it for backup or import into another ClassQuiz instance.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('admin.quizzes.export', $quiz) }}" class="cq-btn-primary cq-btn-sm">Export quiz JSON</a>
                    <a href="{{ route('admin.import') }}" class="cq-btn-secondary cq-btn-sm">Import another quiz</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
