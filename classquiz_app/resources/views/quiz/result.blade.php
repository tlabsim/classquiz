@extends('layouts.quiz')

@section('content')
<div class="bg-white rounded-lg shadow p-8 text-center">
    <div class="text-5xl mb-4">✓</div>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Quiz Submitted</h2>
    <p class="text-gray-500 mb-6">{{ $session->assignment->displayTitle() }}</p>

    @if($showScore && $session->score !== null)
    <div class="inline-block bg-emerald-50 border border-emerald-200 rounded-lg px-8 py-4 mb-6">
        <p class="text-sm text-gray-500">Your Score</p>
        <p class="text-4xl font-bold text-emerald-600 mt-1">
            {{ $session->score }} / {{ $session->max_score }}
        </p>
        @php $pct = $session->max_score > 0 ? round(($session->score / $session->max_score) * 100) : 0; @endphp
        <p class="text-sm text-gray-400 mt-1">{{ $pct }}%</p>
    </div>
    @elseif(!$showScore)
    <p class="text-gray-400 text-sm mb-6">Your score will be available once grading is complete.</p>
    @endif

    <p class="text-sm text-gray-400">You can safely close this window.</p>
</div>
@endsection
