<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $session->assignment->displayTitle() }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50">

{{-- Timer bar --}}
@if($timeRemaining !== null)
<div id="timer-bar" class="fixed top-0 left-0 right-0 bg-emerald-600 text-white text-center py-2 text-sm font-medium z-50">
    Time remaining: <span id="timer">--:--</span>
</div>
@endif

<div class="mx-auto max-w-3xl px-4 {{ $timeRemaining !== null ? 'pt-16' : 'pt-8' }} pb-16">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">{{ $session->assignment->displayTitle() }}</h1>

    @php
        $onePerPage = $session->assignment->setting('question_presentation', 'one_per_page') === 'one_per_page';
        $allowBack = (bool) $session->assignment->setting('allow_modify_previous_answers', true);
    @endphp

    <form id="quiz-form"
          method="POST"
          action="{{ route('quiz.submit', $session->id) }}"
          x-data="{ currentQuestionIndex: 0, onePerPage: @js($onePerPage), allowBack: @js($allowBack), totalQuestions: {{ $orderedQuestions->count() }} }">
        @csrf
        <div class="space-y-6">
            @foreach($orderedQuestions as $index => $question)
            @php $saved = $answeredMap->get($question->id); @endphp
            <div class="cq-question-card"
                 data-question-id="{{ $question->id }}"
                 x-show="!onePerPage || currentQuestionIndex === {{ $index }}"
                 x-cloak>
                <div class="cq-question-header">
                    <div class="flex items-start gap-2">
                        <span class="cq-question-index">{{ $index + 1 }}.</span>
                        <div class="cq-richtext font-medium text-gray-800">{!! $question->text !!}</div>
                    </div>
                    <span class="cq-question-points">{{ $question->points }} pt{{ $question->points != 1 ? 's' : '' }}</span>
                </div>

                @if($question->type === 'mcq_single' || $question->type === 'tf')
                <div class="space-y-2">
                    @foreach($question->choices as $choice)
                    @php $isSelected = in_array($choice->id, $saved?->selected_choice_ids ?? []); @endphp
                    <label class="cq-question-choice cursor-pointer">
                        <input type="radio"
                               name="answers[{{ $index }}][selected_choice_ids][]"
                               value="{{ $choice->id }}"
                               {{ $isSelected ? 'checked' : '' }}
                               class="cq-question-choice-input"
                               onchange="autoSave({{ $question->id }}, [this.value])">
                        <span class="cq-richtext text-sm text-gray-700">{!! $choice->text !!}</span>
                    </label>
                    @endforeach
                </div>
                {{-- Hidden question_id --}}
                <input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question->id }}">

                @elseif($question->type === 'mcq_multi')
                <p class="text-xs text-emerald-600 mb-2">Select all that apply.</p>
                <div class="space-y-2">
                    @foreach($question->choices as $choice)
                    @php $isSelected = in_array($choice->id, $saved?->selected_choice_ids ?? []); @endphp
                    <label class="cq-question-choice cursor-pointer">
                        <input type="checkbox"
                               name="answers[{{ $index }}][selected_choice_ids][]"
                               value="{{ $choice->id }}"
                               {{ $isSelected ? 'checked' : '' }}
                               class="cq-question-choice-input"
                               onchange="autoSaveMulti({{ $question->id }}, {{ $index }})">
                        <span class="cq-richtext text-sm text-gray-700">{!! $choice->text !!}</span>
                    </label>
                    @endforeach
                </div>
                <input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question->id }}">
                @endif
            </div>
            @endforeach
        </div>

        <div class="mt-8 flex items-center justify-between gap-3" x-show="onePerPage" x-cloak>
            <button type="button"
                    @click="if (allowBack && currentQuestionIndex > 0) currentQuestionIndex -= 1"
                    x-show="allowBack && currentQuestionIndex > 0"
                    class="cq-btn cq-btn-secondary">
                Previous
            </button>
            <div class="text-sm text-gray-500" x-text="`Question ${currentQuestionIndex + 1} of ${totalQuestions}`"></div>
            <div class="flex items-center gap-3">
                <button type="button"
                        x-show="currentQuestionIndex < totalQuestions - 1"
                        @click="if (currentQuestionIndex < totalQuestions - 1) currentQuestionIndex += 1"
                        class="cq-btn cq-btn-primary">
                    Next question
                </button>
                <button type="button"
                        x-show="currentQuestionIndex === totalQuestions - 1"
                        onclick="confirmSubmit()"
                        class="cq-btn cq-btn-primary">
                    Submit Quiz
                </button>
            </div>
        </div>

        <div class="mt-8 flex justify-end" x-show="!onePerPage">
            <button type="button" onclick="confirmSubmit()"
                    class="px-8 py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700">
                Submit Quiz
            </button>
        </div>
    </form>
</div>

<script>
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});

const AUTOSAVE_URL = "{{ route('quiz.autosave', $session->id) }}";
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function autoSave(questionId, choiceIds) {
    await fetch(AUTOSAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ question_id: questionId, selected_choice_ids: choiceIds }),
    });
}

function autoSaveMulti(questionId, answerIndex) {
    const boxes = document.querySelectorAll(`input[name="answers[${answerIndex}][selected_choice_ids][]"]:checked`);
    const ids = Array.from(boxes).map(b => parseInt(b.value));
    autoSave(questionId, ids);
}

function confirmSubmit() {
    if (confirm('Are you sure you want to submit? You cannot change your answers after submission.')) {
        document.getElementById('quiz-form').submit();
    }
}

@if($timeRemaining !== null)
let remaining = {{ $timeRemaining }};
const timerEl = document.getElementById('timer');
const timerBar = document.getElementById('timer-bar');

function formatTime(s) {
    const m = Math.floor(s / 60).toString().padStart(2, '0');
    const sec = (s % 60).toString().padStart(2, '0');
    return m + ':' + sec;
}

timerEl.textContent = formatTime(remaining);
const interval = setInterval(() => {
    remaining--;
    if (remaining <= 0) {
        clearInterval(interval);
        timerEl.textContent = '00:00';
        document.getElementById('quiz-form').submit(); // auto-submit
    } else {
        timerEl.textContent = formatTime(remaining);
        if (remaining <= 300) timerBar.classList.add('bg-red-600');
    }
}, 1000);
@endif
</script>
</body>
</html>
