@extends('layouts.admin')

@section('title', 'Session Detail')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.show', $assignment->quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-32">{{ $assignment->quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.assignments.report', [$assignment->quiz, $assignment]) }}" class="hover:text-gray-700 transition-colors">Report</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700 truncate max-w-32">{{ $session->email }}</span>
@endsection

@section('content')

@php
    $statusBadge = match($session->status) {
        'graded'    => 'bg-emerald-100 text-emerald-700',
        'submitted' => 'bg-amber-100 text-amber-700',
        default     => 'bg-gray-100 text-gray-500',
    };
    $timeSpentLabel = $summary['timeSpentSeconds'] !== null
        ? sprintf('%02d:%02d', intdiv($summary['timeSpentSeconds'], 60), $summary['timeSpentSeconds'] % 60)
        : '—';
@endphp

@if($hasLegacyChoiceMismatch)
<div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <p class="font-medium">This is a legacy session with mismatched answer-choice references.</p>
    <p class="mt-1 text-amber-700">The quiz was edited after submission, so the original selected choices can no longer be reconstructed from the current quiz structure. Re-grading is disabled for this session unless a session snapshot exists.</p>
</div>
@endif

{{-- ── Sticky score bar ────────────────────────────────────── --}}
<div class="sticky top-0 z-20 -mx-6 -mt-1 mb-6 px-6 py-3 bg-white/95 backdrop-blur border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.quizzes.assignments.report', [$assignment->quiz, $assignment]) }}"
           class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <p class="font-semibold text-gray-800 text-sm leading-none">{{ $session->email }}</p>
            @if($session->name)
            <p class="text-xs text-gray-400 mt-0.5">{{ $session->name }}</p>
            @endif
        </div>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">
            {{ ucfirst(str_replace('_', ' ', $session->status)) }}
        </span>
    </div>
    <div class="flex items-center gap-4">
        @if($session->score !== null)
        <div class="text-right">
            <p class="text-2xl font-bold text-gray-900 leading-none">{{ $session->score }} <span class="text-sm text-gray-400 font-normal">/ {{ rtrim(rtrim(number_format($effectiveMaxScore, 2, '.', ''), '0'), '.') }}</span></p>
            <p class="text-xs text-gray-400 mt-0.5">total score</p>
        </div>
        @endif
        @if(in_array($session->status, ['submitted', 'graded']) && !$hasLegacyChoiceMismatch)
        <form method="POST" action="{{ route('admin.quizzes.assignments.report.grade', [$assignment->quiz, $assignment, $session]) }}">
            @csrf
            <button type="submit" class="cq-btn-primary cq-btn-sm">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Re-grade
            </button>
        </form>
        @endif
    </div>
</div>

{{-- ── Summary ─────────────────────────────────────────────── --}}
<div class="mb-6 grid gap-4 lg:grid-cols-4">
    <div class="cq-card p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Score</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">
            {{ $session->score !== null ? rtrim(rtrim(number_format($session->score, 2, '.', ''), '0'), '.') : '—' }}
            <span class="text-sm font-normal text-gray-400">/ {{ rtrim(rtrim(number_format($effectiveMaxScore, 2, '.', ''), '0'), '.') }}</span>
        </p>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $summary['scorePercent'] }}%"></div>
        </div>
        <p class="mt-2 text-xs text-gray-500">{{ $summary['scorePercent'] }}% of attainable points</p>
    </div>
    <div class="cq-card p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Questions Answered</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $summary['answeredCount'] }} <span class="text-sm font-normal text-gray-400">/ {{ $summary['totalQuestions'] }}</span></p>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-blue-500" style="width: {{ $summary['questionProgress'] }}%"></div>
        </div>
        <p class="mt-2 text-xs text-gray-500">{{ $summary['questionProgress'] }}% completion</p>
    </div>
    <div class="cq-card p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Answer Breakdown</p>
        <div class="mt-2 flex items-end gap-4">
            <div>
                <p class="text-2xl font-bold text-emerald-600">{{ $summary['correctCount'] }}</p>
                <p class="text-xs text-gray-500">Correct</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-500">{{ $summary['incorrectCount'] }}</p>
                <p class="text-xs text-gray-500">Incorrect</p>
            </div>
        </div>
    </div>
    <div class="cq-card p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Time Spent</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $timeSpentLabel }}</p>
        <p class="mt-2 text-xs text-gray-500">
            @if($assignment->duration_minutes)
            Duration limit {{ $assignment->duration_minutes }} min
            @else
            No duration limit
            @endif
        </p>
    </div>
</div>

{{-- ── Answer cards ────────────────────────────────────────── --}}
<div class="space-y-3">
    @foreach($session->answers as $idx => $answer)
    @php
        $questionSnapshot = $snapshotQuestions->get($answer->question_id);
        $questionText = $answer->question?->text ?? data_get($questionSnapshot, 'text') ?? 'Question no longer available';
        $isCorrect = (bool)$answer->is_correct;
        $pointsAwarded = $answer->points_awarded ?? 0;
        $maxPoints = $answer->question?->points ?? data_get($questionSnapshot, 'points', 0);
        $questionChoices = $answer->question?->choices
            ?? collect(data_get($questionSnapshot, 'choices', []))->map(fn ($choice) => (object) $choice);
        $explanation = $answer->question?->explanation ?? data_get($questionSnapshot, 'explanation');
    @endphp
    <div class="cq-card overflow-hidden">
        {{-- Question header --}}
        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-semibold text-gray-400">Q{{ $idx + 1 }}</span>
                </div>
                <div class="cq-richtext text-sm font-medium text-gray-800 leading-snug">{!! $questionText !!}</div>
            </div>
            <div class="shrink-0 text-right">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold
                    {{ $isCorrect ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                    @if($isCorrect)
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    @else
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    @endif
                    {{ $pointsAwarded }} / {{ $maxPoints }} pts
                </span>
                @if($answer->is_manually_graded)
                <p class="text-xs text-gray-400 mt-1">Manual by {{ $answer->gradedBy?->name ?? 'teacher' }}</p>
                @endif
            </div>
        </div>

        {{-- Choices --}}
        @if($questionChoices->count())
        <ul class="px-5 py-3 space-y-2">
            @foreach($questionChoices as $choice)
            @php
                $choiceId = (int) ($choice->id ?? 0);
                $choiceText = $choice->text ?? '';
                $choiceIsCorrect = (bool) ($choice->is_correct ?? false);
                $selected   = in_array($choiceId, $answer->selected_choice_ids ?? []);
                $colorClass = $selected && $choiceIsCorrect  ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                            : ($selected && !$choiceIsCorrect ? 'bg-red-50 border-red-300 text-red-700'
                            : ($choiceIsCorrect               ? 'bg-emerald-50/40 border-emerald-200 text-emerald-700'
                            :                                       'bg-gray-50 border-gray-200 text-gray-500'));
            @endphp
            <li class="flex items-center gap-3 rounded-lg border px-3 py-2 {{ $colorClass }}">
                <span class="h-5 w-5 shrink-0 flex items-center justify-center rounded-full border-2 transition-colors
                    {{ $selected ? 'border-current' : 'border-gray-300' }}">
                    @if($selected)
                    <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                    @endif
                </span>
                <span class="cq-richtext flex-1 text-sm">{!! $choiceText !!}</span>
                @if($choiceIsCorrect)
                <span class="text-xs font-medium text-emerald-600">Correct</span>
                @endif
                @if($selected && !$choiceIsCorrect)
                <span class="text-xs font-medium text-red-500">Selected</span>
                @endif
            </li>
            @endforeach
        </ul>
        @endif

        @if($explanation)
        <div class="border-t border-gray-100 bg-emerald-50/40 px-5 py-3">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-emerald-500">Explanation</p>
            <p class="text-sm text-gray-700">{{ $explanation }}</p>
        </div>
        @endif

        {{-- Manual override --}}
        <div class="px-5 py-3 bg-gray-50/50 border-t border-gray-100">
            <form method="POST"
                  action="{{ route('admin.quizzes.assignments.report.override', [$assignment->quiz, $assignment, $session, $answer]) }}"
                  class="flex items-center gap-3">
                @csrf
                <label class="text-xs text-gray-500 font-medium">Override points:</label>
                <input type="number" name="points_awarded" value="{{ $answer->points_awarded ?? 0 }}"
                       step="0.5" min="0" max="{{ $maxPoints }}"
                       class="cq-field w-24 text-sm py-1.5">
                <button type="submit" class="cq-btn-secondary cq-btn-sm !py-1.5">Apply</button>
            </form>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Audit details ───────────────────────────────────────── --}}
<div class="cq-card mt-6 p-5">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Session Audit Details</h2>
        <p class="mt-1 text-sm text-gray-500">Reference information for review and post-exam auditing.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 text-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Session ID</p>
            <p class="mt-1 break-all font-medium text-gray-800">{{ $session->id }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Assignment</p>
            <p class="mt-1 font-medium text-gray-800">{{ $assignment->displayTitle() }}</p>
            <p class="text-xs text-gray-500">{{ $assignment->public_token }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Student</p>
            <p class="mt-1 font-medium text-gray-800">{{ $session->email }}</p>
            <p class="text-xs text-gray-500">{{ $session->name ?? 'No name' }}{{ $session->class_id ? ' · ' . $session->class_id : '' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Status</p>
            <p class="mt-1 font-medium text-gray-800">{{ ucfirst(str_replace('_', ' ', $session->status)) }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Started</p>
            <p class="mt-1 font-medium text-gray-800">{{ $session->started_at?->format('Y-m-d h:i:s A') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Submitted</p>
            <p class="mt-1 font-medium text-gray-800">{{ $session->submitted_at?->format('Y-m-d h:i:s A') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Last Activity</p>
            <p class="mt-1 font-medium text-gray-800">{{ $session->last_activity_at?->format('Y-m-d h:i:s A') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Question Order</p>
            <p class="mt-1 font-medium text-gray-800 break-all">{{ $session->question_order ? implode(', ', $session->question_order) : '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Snapshot Captured</p>
            <p class="mt-1 font-medium text-gray-800">{{ data_get($session->quiz_snapshot, 'captured_at') ? \Illuminate\Support\Carbon::parse(data_get($session->quiz_snapshot, 'captured_at'))->format('Y-m-d h:i:s A') : '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Re-grade Safety</p>
            <p class="mt-1 font-medium text-gray-800">{{ $hasLegacyChoiceMismatch ? 'Unsafe from live quiz data' : 'Safe' }}</p>
        </div>
    </div>
</div>

@endsection
