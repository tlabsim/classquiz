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
        ['label' => 'Registered',  'value' => $stats['total'],     'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0', 'color' => 'text-emerald-600 bg-emerald-50'],
        ['label' => 'Submitted',   'value' => $stats['submitted'], 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-amber-600 bg-amber-50'],
        ['label' => 'Graded',      'value' => $stats['graded'],    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'color' => 'text-emerald-600 bg-emerald-50'],
        ['label' => 'Avg Score',   'value' => $stats['avg_score'] !== null ? number_format($stats['avg_score'], 1) . ' pts' : '–', 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z', 'color' => 'text-violet-600 bg-violet-50'],
    ];
@endphp
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach($statCards as $card)
    <div class="cq-card p-4 flex items-center gap-3">
        <div class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center {{ $card['color'] }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
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
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Score</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Submitted</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sessions as $session)
                @php
                    $statusBadge = match($session->status) {
                        'graded'    => 'bg-emerald-100 text-emerald-700',
                        'submitted' => 'bg-amber-100 text-amber-700',
                        default     => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors"
                    x-show="
                        (statusFilter === 'all' || statusFilter === '{{ $session->status }}') &&
                        ('{{ strtolower($session->email) }} {{ strtolower($session->name ?? '') }}'.includes(search.toLowerCase()))
                    ">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $session->email }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $session->name ?? '–' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">
                            {{ ucfirst(str_replace('_', ' ', $session->status)) }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        @if($session->score !== null)
                        <span class="font-medium text-gray-800">{{ $session->score }}</span>
                        <span class="text-gray-400">/ {{ rtrim(rtrim(number_format($effectiveMaxScore, 2, '.', ''), '0'), '.') }}</span>
                        @else
                        <span class="text-gray-400">–</span>
                        @endif
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
                <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">No sessions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">{{ $sessions->links() }}</div>
    @endif
</div>

@endsection
