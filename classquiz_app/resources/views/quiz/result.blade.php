@extends('layouts.quiz')

@section('content')
@php $effectiveMaxScore = $session->effectiveMaxScore(); @endphp
<div class="bg-white rounded-lg shadow p-8 text-center">
    <div class="text-5xl mb-4">✓</div>
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Quiz Submitted</h2>
    <p class="text-gray-500 mb-6">{{ $session->assignment->displayTitle() }}</p>

    @if($showScore && $session->score !== null)
    <div class="inline-block bg-emerald-50 border border-emerald-200 rounded-lg px-8 py-4 mb-6">
        <p class="text-sm text-gray-500">Your Score</p>
        <p class="text-4xl font-bold text-emerald-600 mt-1">
            {{ $session->score }} / {{ rtrim(rtrim(number_format($effectiveMaxScore, 2, '.', ''), '0'), '.') }}
        </p>
        @php $pct = $effectiveMaxScore > 0 ? round(($session->score / $effectiveMaxScore) * 100) : 0; @endphp
        <p class="text-sm text-gray-400 mt-1">{{ $pct }}%</p>
    </div>
    @elseif(!$showScore)
    <p class="text-gray-400 text-sm mb-6">Your score will be available once grading is complete.</p>
    @endif

    @if($showCorrectAnswers)
    <div class="mt-8 text-left">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Answer Review</h3>
        <div class="space-y-4">
            @foreach($session->answers->sortBy(fn ($answer) => $answer->question->sort_order) as $answer)
                @php
                    $question = $answer->question;
                    $selectedChoiceIds = collect($answer->selected_choice_ids ?? [])->map(fn ($id) => (int) $id)->all();
                @endphp
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $question->text }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $answer->points_awarded ?? 0 }} / {{ $question->points }} pts.</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $answer->is_correct ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                            {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-2">
                        @foreach($question->choices as $choice)
                            @php
                                $isSelected = in_array((int) $choice->id, $selectedChoiceIds, true);
                            @endphp
                            <div class="rounded-md border px-3 py-2 text-sm {{ $choice->is_correct ? 'border-emerald-200 bg-emerald-50/70' : 'border-gray-200 bg-white' }}">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-700">{{ $choice->text }}</span>
                                    <div class="flex items-center gap-2 text-xs">
                                        @if($isSelected)
                                            <span class="rounded-full bg-sky-50 px-2 py-0.5 font-medium text-sky-700">Your answer</span>
                                        @endif
                                        @if($choice->is_correct)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700">Correct</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($showFeedbackAndExplanation)
                        <div class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                            @if($answer->is_correct && $question->feedback_correct)
                                <div class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-800">
                                    {{ $question->feedback_correct }}
                                </div>
                            @endif
                            @if(!$answer->is_correct && $question->feedback_incorrect)
                                <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">
                                    {{ $question->feedback_incorrect }}
                                </div>
                            @endif
                            @if($question->explanation)
                                <div class="rounded-md bg-gray-50 p-3 text-sm text-gray-700">
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Explanation</p>
                                    <p>{{ $question->explanation }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <p class="text-sm text-gray-400">You can safely close this window.</p>
</div>
@endsection
