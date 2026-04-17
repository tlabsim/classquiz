@extends('layouts.admin')

@section('title', 'Live')

@section('breadcrumb')
    <span class="text-gray-700">Live</span>
@endsection

@section('suppress_admin_alerts', true)

@section('content')
<div x-data x-init="setTimeout(() => window.location.reload(), 15000)"></div>

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="cq-page-title">Live</h1>
        <p class="mt-1 text-sm text-gray-500">Monitor active quiz sessions and current progress.</p>
    </div>
    <div class="rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-500 ring-1 ring-gray-200">
        Updated from current session activity
    </div>
</div>

<div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach([
        ['label' => 'Live now', 'value' => $stats['live'], 'tone' => 'bg-emerald-50 text-emerald-700'],
        ['label' => 'In progress', 'value' => $stats['in_progress'], 'tone' => 'bg-blue-50 text-blue-700'],
        ['label' => 'Ready to start', 'value' => $stats['ready'], 'tone' => 'bg-amber-50 text-amber-700'],
        ['label' => 'Avg progress', 'value' => $stats['avg_question_progress'] . '%', 'tone' => 'bg-gray-100 text-gray-700'],
    ] as $card)
        <div class="cq-card px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $card['tone'] }}">{{ $card['label'] }}</span>
                <p class="text-2xl font-semibold text-gray-900 tabular-nums">{{ $card['value'] }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="cq-card overflow-hidden" x-data="{ search: '' }">
    <div class="border-b border-gray-100 px-5 py-3.5">
        <div class="relative max-w-md">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Search by student, email, quiz, or assignment..."
                   class="cq-field pl-9 text-sm">
        </div>
    </div>

    @if($liveAssignments->isEmpty())
        <div class="px-6 py-12 text-center">
            <p class="text-sm text-gray-500">No live sessions right now.</p>
            <p class="mt-1 text-xs text-gray-400">Sessions will appear here once students start a quiz.</p>
        </div>
    @else
        <div class="space-y-5">
            @foreach($liveAssignments as $assignment)
                @php
                    $quiz = $assignment->quiz;
                    $sessions = $assignment->live_sessions;
                    $assignmentSearchBlob = strtolower($quiz->title . ' ' . $assignment->displayTitle());
                @endphp
                <div x-show="'{{ $assignmentSearchBlob }}'.includes(search.toLowerCase()) || [...$el.querySelectorAll('[data-live-search]')].some(node => node.dataset.liveSearch.includes(search.toLowerCase()))">
                    <div class="border-b border-gray-100 px-5 py-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">{{ $assignment->displayTitle() }}</h2>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $quiz->title }} · {{ $sessions->count() }} live {{ \Illuminate\Support\Str::plural('session', $sessions->count()) }}</p>
                            </div>
                            <a href="{{ route('admin.quizzes.assignments.show', [$quiz, $assignment]) }}"
                               class="cq-btn-secondary cq-btn-sm !py-1 !px-2.5 text-xs">
                                Assignment
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50/70">
                                <tr>
                                    <th class="w-10 px-4 py-3"></th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Student</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Questions</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Live score</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Time</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Last active</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            @foreach($sessions as $session)
                        @php
                            $statusClass = $session->status === 'in_progress'
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-blue-100 text-blue-700';
                            $timeElapsedSeconds = $session->live_time_elapsed_seconds;
                            $timeElapsedLabel = $timeElapsedSeconds !== null
                                ? sprintf('%02d:%02d', intdiv($timeElapsedSeconds, 60), $timeElapsedSeconds % 60)
                                : null;
                            $durationLabel = $assignment->duration_minutes
                                ? $assignment->duration_minutes . ' min'
                                : null;
                            $timeDisplayLabel = $timeElapsedLabel && $durationLabel
                                ? $timeElapsedLabel . '/' . $durationLabel
                                : null;
                            $searchBlob = strtolower(implode(' ', array_filter([
                                $session->name,
                                $session->email,
                                $session->class_id,
                            ])));
                        @endphp
                        <tbody class="divide-y divide-gray-50"
                               x-data="{ open: false }"
                               x-show="'{{ $searchBlob }}'.includes(search.toLowerCase())"
                               data-live-search="{{ $searchBlob }}">
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3">
                                    <button type="button" @click="open = !open"
                                            class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                        <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': open }"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900">{{ $session->name ?: 'Unnamed taker' }}</p>
                                    <p class="text-xs text-gray-500">{{ $session->email }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $session->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="min-w-40">
                                        <div class="mb-1 flex items-center justify-between text-xs text-gray-500">
                                            <span>{{ $session->live_answered_count }}/{{ $session->live_total_questions }}</span>
                                            <span>{{ $session->live_question_progress }}%</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-gray-100">
                                            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $session->live_question_progress }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-xs text-gray-600">
                                    <span class="font-medium text-gray-900">{{ rtrim(rtrim(number_format((float) $session->live_score, 2, '.', ''), '0'), '.') }}</span>
                                    <span class="text-gray-400">/ {{ rtrim(rtrim(number_format((float) $session->live_max_score, 2, '.', ''), '0'), '.') }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    @if($assignment->duration_minutes && $session->live_time_progress !== null)
                                        <div class="min-w-32">
                                            <div class="mb-1 flex items-center justify-between text-xs text-gray-500">
                                                <span>{{ $timeDisplayLabel }}</span>
                                                <span>{{ $session->live_time_progress }}%</span>
                                            </div>
                                            <div class="h-2 rounded-full bg-gray-100">
                                                <div class="h-2 rounded-full bg-amber-500" style="width: {{ $session->live_time_progress }}%"></div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">No time limit</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs text-gray-500">
                                    {{ optional($session->last_activity_at ?? $session->started_at)->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.quizzes.assignments.report.session', [$quiz, $assignment, $session]) }}"
                                       class="cq-btn-secondary cq-btn-sm !px-2.5 !py-1 text-xs">
                                        Details
                                    </a>
                                </td>
                            </tr>
                            <tr x-show="open" x-cloak class="bg-gray-50/60">
                                <td colspan="8" class="px-5 py-4">
                                    <div class="grid gap-4 text-xs text-gray-600 lg:grid-cols-4">
                                        <div>
                                            <p class="font-semibold uppercase tracking-wide text-gray-400">Student</p>
                                            <p class="mt-1">{{ $session->email }}</p>
                                            @if($session->class_id)
                                                <p class="mt-1">Class ID: {{ $session->class_id }}</p>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-semibold uppercase tracking-wide text-gray-400">Session</p>
                                            <p class="mt-1">Started: {{ $session->started_at?->diffForHumans() ?? 'Not started' }}</p>
                                            <p class="mt-1">Last active: {{ $session->last_activity_at?->diffForHumans() ?? 'No activity yet' }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold uppercase tracking-wide text-gray-400">Coverage</p>
                                            <p class="mt-1">{{ $session->live_answered_count }} answered of {{ $session->live_total_questions }}</p>
                                            <p class="mt-1">{{ $session->answers_count }} saved answer {{ \Illuminate\Support\Str::plural('record', $session->answers_count) }}</p>
                                            <p class="mt-1">Current score: {{ rtrim(rtrim(number_format((float) $session->live_score, 2, '.', ''), '0'), '.') }} / {{ rtrim(rtrim(number_format((float) $session->live_max_score, 2, '.', ''), '0'), '.') }}</p>
                                        </div>
                                        <div>
                                            <p class="font-semibold uppercase tracking-wide text-gray-400">Assignment</p>
                                            <p class="mt-1">{{ $assignment->duration_minutes ? $assignment->duration_minutes . ' min duration' : 'No time limit' }}</p>
                                            @if($assignment->duration_minutes && $timeDisplayLabel)
                                                <p class="mt-1">Time elapsed: {{ $timeDisplayLabel }}</p>
                                            @endif
                                            <p class="mt-1">{{ $assignment->access_code_required ? 'Access code required' : 'Direct start' }}</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
