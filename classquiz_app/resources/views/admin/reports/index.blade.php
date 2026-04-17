@extends('layouts.admin')

@section('title', 'Report')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.show', $assignment->quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-36">{{ $assignment->quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.assignments.show', [$assignment->quiz, $assignment]) }}" class="hover:text-gray-700 transition-colors truncate max-w-36">{{ $assignment->displayTitle() }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700">Report</span>
@endsection

@section('content')

@php
    $sortIndicator = function (string $column) use ($sort, $direction) {
        if ($sort !== $column) {
            return '<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16.29 14.29L12 18.59l-4.29-4.3a1 1 0 0 0-1.42 1.42l5 5a1 1 0 0 0 1.42 0l5-5a1 1 0 0 0-1.42-1.42M7.71 9.71L12 5.41l4.29 4.3a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.42l-5-5a1 1 0 0 0-1.42 0l-5 5a1 1 0 0 0 1.42 1.42"/></svg>';
        }

        if ($direction === 'asc') {
            return '<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17h12M4 12h9M4 7h6m8 6V5m0 0l3 3m-3-3l-3 3"/></svg>';
        }

        return '<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17h6m-6-5h9m5-1v8m0 0l3-3m-3 3l-3-3M4 7h12"/></svg>';
    };

    $sortUrl = function (string $column) use ($sort, $direction, $sessions) {
        return $sessions->path() . '?' . http_build_query(array_merge(
            request()->except('page'),
            [
                'sort' => $column,
                'direction' => $sort === $column && $direction === 'desc' ? 'asc' : 'desc',
            ],
        ));
    };
@endphp

{{-- ── Header ──────────────────────────────────────────────── --}}
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="cq-page-title">Report</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $assignment->displayTitle() }}</p>
    </div>
    <a href="{{ route('admin.quizzes.assignments.report.export', [$assignment->quiz, $assignment]) }}"
       class="cq-btn-secondary cq-btn-sm">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16"/>
        </svg>
        Export CSV
    </a>
</div>

{{-- ── Stat cards ──────────────────────────────────────────── --}}
@php
    $statCards = [
        ['label' => 'Registered',  'value' => $stats['total'],     'icon' => '<path d="M15.998 0c-.901 0-1.807.198-2.49.594L4.338 5.888C2.966 6.68 1.844 8.622 1.844 10.205v10.589c0 1.584 1.122 3.527 2.494 4.319l9.17 5.294c.683.395 1.589.592 2.49.592c.905 0 1.81-.197 2.493-.592l9.17-5.294c1.37-.792 2.494-2.735 2.494-4.319V10.205c0-1.583-1.124-3.525-2.494-4.317L18.491.594C17.808.198 16.903 0 15.998 0m6.2 9.023c.264 0 .528.1.728.301l1.116 1.114c.4.402.4 1.059 0 1.46L14.264 21.676c-.404.401-1.059.401-1.459 0L8.495 17.37c-.4-.4-.315-1.141 0-1.458l1.116-1.114c.4-.402 1.06-.402 1.46 0l1.734 1.733c.4.401 1.055.401 1.459 0l7.202-7.205a1.035 1.035 0 0 1 .731-.301"/>', 'viewBox' => '0 0 32 32', 'color' => 'text-emerald-600 bg-emerald-50'],
        ['label' => 'Submitted',   'value' => $stats['submitted'], 'icon' => '<path d="M29.653 1.241a1.271 1.271 0 0 0-1.349-.174L2.109 13.363v2.38l11.002 4.401L20.168 31h2.38l7.519-28.463a1.271 1.271 0 0 0-.414-1.296M21.078 28.731l-6.066-9.332 9.335-10.224l-1.477-1.349l-9.408 10.305l-9.072-3.628L27.731 3.545Z"/>', 'viewBox' => '0 0 32 32', 'color' => 'text-amber-600 bg-amber-50'],
        ['label' => 'Graded',      'value' => $stats['graded'],    'icon' => '<g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"><path d="M8.5 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6m3.5-18H18a2 2 0 0 1 2 2v9"/><path d="M8 6.4V4.5a.5.5 0 0 1 .5-.5c.276 0 .504-.224.552-.496C9.2 2.652 9.774 1 12 1s2.8 1.652 2.948 2.504c.048.272.276.496.552.496a.5.5 0 0 1 .5.5v1.9a.6.6 0 0 1-.6.6H8.6a.6.6 0 0 1-.6-.6Z"/><path stroke-linejoin="round" d="m15.5 20.5l2 2l5-5"/></g>', 'viewBox' => '0 0 24 24', 'color' => 'text-emerald-600 bg-emerald-50'],
        ['label' => 'Avg Score',   'value' => $stats['avg_score'] !== null ? number_format($stats['avg_score'], 1) . ' pts' : '–', 'icon' => '<g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"><path d="M21 21H10c-3.3 0-4.95 0-5.975-1.025S3 17.3 3 14V3"/><path stroke-linejoin="round" d="M6 12h.009m2.99 0h.008m2.99 0h.008m2.99 0h.009m2.989 0h.009m2.989 0H21"/><path d="M6 7c.673-1.122 1.587-2 2.993-2c5.943 0 2.602 12 8.989 12c1.416 0 2.324-.884 3.018-2"/></g>', 'viewBox' => '0 0 24 24', 'color' => 'text-violet-600 bg-violet-50'],
    ];
@endphp
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach($statCards as $card)
    <div class="cq-card p-4 flex items-center gap-3">
        <div class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center {{ $card['color'] }}">
            <svg class="h-5 w-5" viewBox="{{ $card['viewBox'] }}" fill="currentColor" aria-hidden="true">
                {!! $card['icon'] !!}
            </svg>
        </div>
        <div>
            <p class="text-xl font-bold text-gray-900 leading-none">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $card['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Sessions table ──────────────────────────────────────── --}}
<div class="cq-card overflow-hidden" x-data="{ statusFilter: 'all', search: '' }">

    {{-- Table toolbar --}}
    <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-48">
            <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Search by email or name…"
                   class="cq-field pl-9 text-sm">
        </div>
        <div class="flex items-center gap-1.5">
            @foreach(['all' => 'All', 'in_progress' => 'In progress', 'submitted' => 'Submitted', 'graded' => 'Graded'] as $val => $lbl)
            <button type="button"
                    @click="statusFilter = '{{ $val }}'"
                    :class="statusFilter === '{{ $val }}' ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                    class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors">{{ $lbl }}</button>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50/70">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Student</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <a href="{{ $sortUrl('questions') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Questions</span>
                            <span class="text-gray-400">{!! $sortIndicator('questions') !!}</span>
                        </a>
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <a href="{{ $sortUrl('score') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Score</span>
                            <span class="text-gray-400">{!! $sortIndicator('score') !!}</span>
                        </a>
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <a href="{{ $sortUrl('time') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Time</span>
                            <span class="text-gray-400">{!! $sortIndicator('time') !!}</span>
                        </a>
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <a href="{{ $sortUrl('last_active') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Last active</span>
                            <span class="text-gray-400">{!! $sortIndicator('last_active') !!}</span>
                        </a>
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <a href="{{ $sortUrl('submitted') }}" class="inline-flex items-center gap-1 hover:text-gray-700">
                            <span>Submitted</span>
                            <span class="text-gray-400">{!! $sortIndicator('submitted') !!}</span>
                        </a>
                    </th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sessions as $session)
                @php
                    $statusBadge = match($session->status) {
                        'graded'    => 'bg-emerald-100 text-emerald-700',
                        'submitted' => 'bg-amber-100 text-amber-700',
                        'in_progress' => 'bg-blue-100 text-blue-700',
                        default     => 'bg-gray-100 text-gray-500',
                    };
                    $timeSeconds = $session->report_time_seconds;
                    $timeLabel = $timeSeconds !== null
                        ? sprintf('%02d:%02d', intdiv($timeSeconds, 60), $timeSeconds % 60)
                        : '–';
                    $lastActiveLabel = $session->last_activity_at?->diffForHumans()
                        ?? $session->updated_at?->diffForHumans()
                        ?? '–';
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors"
                    x-show="
                        (statusFilter === 'all' || statusFilter === '{{ $session->status }}') &&
                        ('{{ strtolower($session->email) }} {{ strtolower($session->name ?? '') }}'.includes(search.toLowerCase()))
                    ">
                    <td class="px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-800">{{ $session->email }}</p>
                            <p class="mt-0.5 truncate text-sm text-gray-500">{{ $session->name ?? 'No name' }}</p>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">
                            {{ ucfirst(str_replace('_', ' ', $session->status)) }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="min-w-28">
                            <div class="flex items-center justify-between gap-2 text-xs font-medium text-gray-600">
                                <span>{{ $session->report_answered_count }}/{{ $session->report_total_questions }}</span>
                                <span>{{ $session->report_question_progress }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $session->report_question_progress }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        @if($session->score !== null)
                        <span class="font-medium text-gray-800">{{ $session->score }}</span>
                        <span class="text-gray-400">/ {{ rtrim(rtrim(number_format($effectiveMaxScore, 2, '.', ''), '0'), '.') }}</span>
                        @else
                        <span class="text-gray-400">–</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs font-medium">
                        {{ $timeLabel }}
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">
                        {{ $lastActiveLabel }}
                    </td>
                    <td class="px-5 py-3 text-gray-400 text-xs">
                        {{ $session->submitted_at?->diffForHumans() ?? '–' }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if(in_array($session->status, ['submitted', 'graded']))
                            <form method="POST" action="{{ route('admin.quizzes.assignments.report.grade', [$assignment->quiz, $assignment, $session]) }}">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors">Re-grade</button>
                            </form>
                            @endif
                            <a href="{{ route('admin.quizzes.assignments.report.session', [$assignment->quiz, $assignment, $session]) }}"
                               class="cq-btn-secondary cq-btn-sm !py-1 !px-2.5 text-xs">Details</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-10 text-center text-sm text-gray-400">No sessions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">{{ $sessions->links() }}</div>
    @endif
</div>

@endsection
