@php
    $isEdit = isset($question);
    $questionType = old('type', $question->type ?? 'mcq_single');
    $questionText = old('text', $question->text ?? '');
    $questionPoints = old('points', $question->points ?? 1);
    $randomizeChoices = old(
        'settings.randomize_choices',
        $isEdit ? $question->setting('randomize_choices') : true
    );
    $richChoices = old(
        'settings.rich_choices',
        $isEdit ? $question->setting('rich_choices') : false
    );
    $multiGrading = old(
        'settings.mcq_multi_grading',
        $isEdit ? $question->setting('mcq_multi_grading') : 'all_or_nothing'
    );
    $tag = old('tag', $question->tag ?? '');
    $existingTag = old('existing_tag', $tag ? ($tags->contains($tag) ? $tag : '__new__') : '');
    $newTag = old('new_tag', $tag && !$tags->contains($tag) ? $tag : '');
    $feedbackCorrect = old('feedback_correct', $question->feedback_correct ?? '');
    $feedbackIncorrect = old('feedback_incorrect', $question->feedback_incorrect ?? '');
    $explanation = old('explanation', $question->explanation ?? '');

    $questionChoices = old('choices')
        ? collect(old('choices'))->values()->map(fn ($choice, $index) => [
            'text' => $choice['text'] ?? '',
            'is_correct' => ! empty($choice['is_correct']),
            'sort_order' => $choice['sort_order'] ?? $index,
        ])->all()
        : ($isEdit
            ? $question->choices->map(fn ($choice) => [
                'text' => $choice->text,
                'is_correct' => (bool) $choice->is_correct,
                'sort_order' => $choice->sort_order,
            ])->values()->all()
            : [
                ['text' => '', 'is_correct' => false, 'sort_order' => 0],
                ['text' => '', 'is_correct' => false, 'sort_order' => 1],
            ]);

    $questionTfCorrect = old('tf_correct');

    if ($questionTfCorrect === null) {
        if ($isEdit && $question->type === 'tf') {
            $falseChoice = $question->choices->firstWhere('text', 'False');
            $questionTfCorrect = $falseChoice?->is_correct ? 'false' : 'true';
        } else {
            $questionTfCorrect = 'true';
        }
    }

    $questionFormConfig = [
        'type' => $questionType,
        'text' => $questionText,
        'points' => (string) $questionPoints,
        'choices' => $questionChoices,
        'tfCorrect' => $questionTfCorrect,
        'richChoices' => (bool) $richChoices,
        'existingTag' => $existingTag,
        'newTag' => $newTag,
        'uploadUrl' => route('admin.question-images.store'),
        'csrf' => csrf_token(),
    ];
    $formErrors = collect($errors->all())->unique()->values();
    @endphp

<div class="mx-auto max-w-3xl"
     x-data="window.questionForm(@js($questionFormConfig))"
     x-init="init()">
    @if($formErrors->isNotEmpty())
        <div x-show="showFormNotice"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="sticky top-20 z-40 pb-4">
            <div class="rounded-2xl bg-white/95 shadow-[0_20px_60px_rgba(15,23,42,0.16)] backdrop-blur-md">
                <div class="flex items-start gap-4 px-5 py-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M4.93 19.07A10 10 0 1119.07 4.93A10 10 0 014.93 19.07z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">Please fix the following before saving</p>
                        <ul class="mt-2 space-y-1 text-sm text-gray-600">
                            @foreach($formErrors as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button"
                            @click="showFormNotice = false"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

<form method="POST"
      action="{{ $action }}"
      class="relative"
      @submit="syncAllEditors()">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <input type="hidden" name="type" :value="type">
    <textarea x-ref="textInput" name="text" class="hidden" x-model="text"></textarea>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="cq-page-title">{{ $title }}</h1>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <p class="text-sm text-gray-500">{{ $quiz->title }}</p>
                <span class="cq-badge-blue">{{ trans_choice(':count question|:count questions', $quiz->questions()->count(), ['count' => $quiz->questions()->count()]) }}</span>
            </div>
        </div>
        <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="cq-btn cq-btn-secondary cq-btn-sm">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to questions
        </a>
    </div>

    <div class="cq-card mb-4 px-6 py-5">
        <p class="cq-form-section-title mb-4">Question type</p>
        <div class="grid grid-cols-3 gap-3">
            @foreach([
                ['mcq_single', 'MCQ - Single correct'],
                ['mcq_multi', 'MCQ - Multi correct'],
                ['tf', 'True / False'],
            ] as [$value, $label])
                <button type="button"
                        @click="setType('{{ $value }}')"
                        :class="type === '{{ $value }}'
                            ? 'border-emerald-500 bg-emerald-50 shadow-sm'
                            : 'border-gray-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/40'"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border-2 p-4 text-center transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-1">
                    <span class="text-xs font-semibold leading-tight transition-colors"
                          :class="type === '{{ $value }}' ? 'text-emerald-700' : 'text-gray-500 group-hover:text-emerald-600'">
                        {{ $label }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="cq-card mb-4 space-y-4 px-6 py-5">
        <div>
            <label class="cq-form-section-title mb-3 block">
                Question body <span class="text-red-500">*</span>
            </label>

            <div class="overflow-hidden rounded-xl border {{ $errors->has('text') ? 'border-red-300' : 'border-gray-200' }} bg-white shadow-sm">
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-gray-50 px-3 py-2">
                    <button type="button" class="cq-editor-btn" @click="formatQuestion('bold')" title="Bold"><strong>B</strong></button>
                    <button type="button" class="cq-editor-btn italic" @click="formatQuestion('italic')" title="Italic">I</button>
                    <button type="button" class="cq-editor-btn underline" @click="formatQuestion('underline')" title="Underline">U</button>
                    <span class="h-5 w-px bg-gray-200"></span>
                    <button type="button" class="cq-editor-btn" @click="formatQuestion('insertUnorderedList')" title="Bulleted list">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                        </svg>
                    </button>
                    <button type="button" class="cq-editor-btn" @click="formatQuestion('insertOrderedList')" title="Numbered list">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M3 22v-1.5h2.5v-.75H4v-1.5h1.5v-.75H3V16h3q.425 0 .713.288T7 17v1q0 .425-.288.713T6 19q.425 0 .713.288T7 20v1q0 .425-.288.713T6 22zm0-7v-2.75q0-.425.288-.712T4 11.25h1.5v-.75H3V9h3q.425 0 .713.288T7 10v1.75q0 .425-.288.713T6 12.75H4.5v.75H7V15zm1.5-7V3.5H3V2h3v6zM9 19v-2h12v2zm0-6v-2h12v2zm0-6V5h12v2z"/>
                        </svg>
                    </button>
                    <span class="h-5 w-px bg-gray-200"></span>
                    <button type="button" class="cq-editor-btn" @click="insertQuestionLink()" title="Insert link">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.415 4.84a4.775 4.775 0 0 1 6.752 6.752l-.013.013l-2.264 2.265a4.776 4.776 0 0 1-7.201-.516a1 1 0 0 1 1.601-1.198a2.774 2.774 0 0 0 4.185.3l2.259-2.259a2.776 2.776 0 0 0-3.925-3.923L12.516 7.56a1 1 0 0 1-1.41-1.418l1.298-1.291zM8.818 9.032a4.775 4.775 0 0 1 5.492 1.614a1 1 0 0 1-1.601 1.198a2.775 2.775 0 0 0-4.185-.3l-2.258 2.259a2.775 2.775 0 0 0 3.923 3.924l1.285-1.285a1 1 0 1 1 1.414 1.414l-1.291 1.291l-.012.013a4.775 4.775 0 0 1-6.752-6.752l.012-.013L7.11 10.13a4.8 4.8 0 0 1 1.708-1.098" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <button type="button" class="cq-editor-btn" @click="insertQuestionImageUrl()" title="Insert image by URL">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M6.188 2h11.625A4.187 4.187 0 0 1 22 6.188v11.625A4.187 4.187 0 0 1 17.812 22H6.188A4.187 4.187 0 0 1 2 17.812V6.188A4.19 4.19 0 0 1 6.188 2m0 2C4.979 4 4 4.98 4 6.188v11.625C4 19.02 4.98 20 6.188 20h11.625C19.02 20 20 19.02 20 17.812V6.188C20 4.98 19.02 4 17.812 4z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M17.24 10.924a1.19 1.19 0 0 0-1.51-.013l-5.244 4.247a2.094 2.094 0 0 1-2.59.035l-1.385-1.06a.094.094 0 0 0-.122.007l-2.698 2.582a1 1 0 1 1-1.382-1.444l2.697-2.583a2.094 2.094 0 0 1 2.721-.15l1.385 1.06a.094.094 0 0 0 .116-.001l5.242-4.247a3.19 3.19 0 0 1 4.053.033l3.12 2.613a1 1 0 0 1-1.285 1.533z" clip-rule="evenodd"/><path d="M10.281 8.64a1.64 1.64 0 1 1-3.28 0a1.64 1.64 0 0 1 3.28 0"/>
                        </svg>
                    </button>
                    <button type="button" class="cq-editor-btn" @click="$refs.questionImageUpload.click()" :disabled="uploadingImage" title="Upload image">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M19 13a1 1 0 0 0-1 1v.38l-1.48-1.48a2.79 2.79 0 0 0-3.93 0l-.7.7l-2.48-2.48a2.85 2.85 0 0 0-3.93 0L4 12.6V7a1 1 0 0 1 1-1h7a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-5a1 1 0 0 0-1-1M5 20a1 1 0 0 1-1-1v-3.57l2.9-2.9a.79.79 0 0 1 1.09 0l3.17 3.17l4.3 4.3Zm13-1a.9.9 0 0 1-.18.53L13.31 15l.7-.7a.77.77 0 0 1 1.1 0L18 17.21Zm4.71-14.71l-3-3a1 1 0 0 0-.33-.21a1 1 0 0 0-.76 0a1 1 0 0 0-.33.21l-3 3a1 1 0 0 0 1.42 1.42L18 4.41V10a1 1 0 0 0 2 0V4.41l1.29 1.3a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.42"/>
                        </svg>
                    </button>
                    <input x-ref="questionImageUpload" type="file" accept="image/*" class="hidden" @change="uploadQuestionImage($event)">
                </div>

                <div x-ref="questionEditor"
                     contenteditable="true"
                     @input="syncQuestionEditor()"
                     @blur="syncQuestionEditor()"
                     class="cq-editor min-h-40 px-4 py-3 text-base text-gray-800 focus:outline-none"></div>
            </div>

        </div>

        <div class="flex flex-wrap items-end gap-5">
            <div>
                <label for="q-points" class="mb-1.5 block text-sm font-medium text-gray-700">Points</label>
                <input id="q-points" type="number" name="points" x-model="points"
                       step="0.5" min="0" class="cq-field w-28 text-sm">
            </div>
        </div>
    </div>

    <div x-show="type === 'tf'" x-cloak class="cq-card mb-4 px-6 py-5">
        <input type="hidden" name="tf_correct" :value="tfCorrect">
        <p class="mb-3 text-sm font-semibold text-gray-700">Correct answer</p>
        <div class="flex gap-3">
            <button type="button" @click="tfCorrect = 'true'"
                    :class="tfCorrect === 'true'
                        ? 'border-emerald-400 bg-emerald-50'
                        : 'border-gray-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/40'"
                    class="flex flex-1 items-center gap-3 rounded-xl border-2 px-5 py-4 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-1">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                      :class="tfCorrect === 'true' ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300'">
                    <svg x-show="tfCorrect === 'true'" class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <span class="text-sm font-semibold transition-colors"
                      :class="tfCorrect === 'true' ? 'text-emerald-700' : 'text-gray-600'">True</span>
            </button>

            <button type="button" @click="tfCorrect = 'false'"
                    :class="tfCorrect === 'false'
                        ? 'border-red-400 bg-red-50'
                        : 'border-gray-200 bg-white hover:border-red-200 hover:bg-red-50/40'"
                    class="flex flex-1 items-center gap-3 rounded-xl border-2 px-5 py-4 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                      :class="tfCorrect === 'false' ? 'border-red-500 bg-red-500' : 'border-gray-300'">
                    <svg x-show="tfCorrect === 'false'" class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <span class="text-sm font-semibold transition-colors"
                      :class="tfCorrect === 'false' ? 'text-red-700' : 'text-gray-600'">False</span>
            </button>
        </div>
    </div>

    <div x-show="type !== 'tf'" x-cloak class="cq-card mb-4 px-6 py-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="cq-form-section-title">Answer choices</p>

            <div class="flex flex-wrap items-center gap-4">
                <label for="randomize"
                       class="flex cursor-pointer items-center gap-2 text-sm text-gray-600"
                       data-tooltip="Show choices in a different order for students each time the question appears.">
                    <input type="hidden" name="settings[randomize_choices]" value="0">
                    <input id="randomize" type="checkbox" name="settings[randomize_choices]" value="1"
                           {{ $randomizeChoices ? 'checked' : '' }}
                           class="h-4 w-4 cursor-pointer rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Shuffle choices</span>
                </label>
            </div>
        </div>

        <div x-show="type === 'mcq_multi'" class="mb-4">
            <label for="mcq-multi-grading" class="mb-1.5 flex items-center gap-2 text-sm font-medium text-gray-700">
                <span>Grading</span>
                <button type="button"
                        class="cq-tooltip-anchor"
                        data-tooltip-placement="right"
                        data-tooltip-align="center"
                        data-tooltip-max-width="22rem"
                        data-tooltip="All or nothing awards full points only for a perfect selection. Partial with deduction gives credit for correct picks and subtracts for wrong picks. Partial without deduction gives credit for correct picks without subtracting for wrong picks.">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 17.75a.75.75 0 0 0 .75-.75v-6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75M12 7a1 1 0 1 1 0 2a1 1 0 0 1 0-2"/>
                        <path fill-rule="evenodd" d="M1.25 12C1.25 6.063 6.063 1.25 12 1.25S22.75 6.063 22.75 12S17.937 22.75 12 22.75S1.25 17.937 1.25 12M12 2.75a9.25 9.25 0 1 0 0 18.5a9.25 9.25 0 0 0 0-18.5" clip-rule="evenodd"/>
                    </svg>
                </button>
            </label>
            <select id="mcq-multi-grading" name="settings[mcq_multi_grading]" class="cq-field text-sm">
                <option value="all_or_nothing" {{ $multiGrading === 'all_or_nothing' ? 'selected' : '' }}>All or nothing</option>
                <option value="partial_with_deduction" {{ $multiGrading === 'partial_with_deduction' ? 'selected' : '' }}>Partial with deduction</option>
                <option value="partial_without_deduction" {{ $multiGrading === 'partial_without_deduction' ? 'selected' : '' }}>Partial without deduction</option>
            </select>
        </div>

        <div class="space-y-3">
            <template x-for="(choice, i) in choices" :key="i">
                <div class="group rounded-lg border px-3 py-2.5 transition-colors duration-100"
                     :class="choice.is_correct
                         ? 'border-emerald-300 bg-emerald-50/70'
                         : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'">
                    <input type="hidden" :name="'choices[' + i + '][sort_order]'" :value="i">

                    <div class="flex gap-3" :class="richChoices ? 'items-start' : 'items-center'">
                        <div :class="richChoices ? 'pt-2' : ''">
                            <template x-if="type === 'mcq_single'">
                                <input type="radio"
                                       name="choice_correct_single"
                                       value="1"
                                       :checked="choice.is_correct"
                                       @change="markSingleCorrect(i)"
                                       class="h-4 w-4 shrink-0 cursor-pointer border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            </template>

                            <template x-if="type === 'mcq_multi'">
                                <div>
                                    <input type="hidden" :name="'choices[' + i + '][is_correct]'" value="0">
                                    <input type="checkbox"
                                           :name="'choices[' + i + '][is_correct]'"
                                           value="1"
                                           x-model="choice.is_correct"
                                           class="h-4 w-4 shrink-0 cursor-pointer rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                </div>
                            </template>

                            <template x-if="type === 'mcq_single'">
                                <input type="hidden" :name="'choices[' + i + '][is_correct]'" :value="choice.is_correct ? 1 : 0">
                            </template>
                        </div>

                        <div class="flex-1">
                            <template x-if="!richChoices">
                                <input type="text"
                                       :name="'choices[' + i + '][text]'"
                                       x-model="choice.text"
                                       :placeholder="'Choice ' + (i + 1)"
                                       data-choice-input
                                       class="w-full border-0 bg-transparent p-0 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-0">
                            </template>

                            <template x-if="richChoices">
                                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                                    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-gray-50 px-3 py-2">
                                        <button type="button" class="cq-editor-btn" @click="formatChoice(i, 'bold')" title="Bold"><strong>B</strong></button>
                                        <button type="button" class="cq-editor-btn italic" @click="formatChoice(i, 'italic')" title="Italic">I</button>
                                        <button type="button" class="cq-editor-btn underline" @click="formatChoice(i, 'underline')" title="Underline">U</button>
                                        <span class="h-5 w-px bg-gray-200"></span>
                                        <button type="button" class="cq-editor-btn" @click="insertChoiceLink(i)" title="Insert link">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.415 4.84a4.775 4.775 0 0 1 6.752 6.752l-.013.013l-2.264 2.265a4.776 4.776 0 0 1-7.201-.516a1 1 0 0 1 1.601-1.198a2.774 2.774 0 0 0 4.185.3l2.259-2.259a2.776 2.776 0 0 0-3.925-3.923L12.516 7.56a1 1 0 0 1-1.41-1.418l1.298-1.291zM8.818 9.032a4.775 4.775 0 0 1 5.492 1.614a1 1 0 0 1-1.601 1.198a2.775 2.775 0 0 0-4.185-.3l-2.258 2.259a2.775 2.775 0 0 0 3.923 3.924l1.285-1.285a1 1 0 1 1 1.414 1.414l-1.291 1.291l-.012.013a4.775 4.775 0 0 1-6.752-6.752l.012-.013L7.11 10.13a4.8 4.8 0 0 1 1.708-1.098" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="cq-editor-btn" @click="formatChoice(i, 'insertOrderedList')" title="Numbered list">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M3 22v-1.5h2.5v-.75H4v-1.5h1.5v-.75H3V16h3q.425 0 .713.288T7 17v1q0 .425-.288.713T6 19q.425 0 .713.288T7 20v1q0 .425-.288.713T6 22zm0-7v-2.75q0-.425.288-.712T4 11.25h1.5v-.75H3V9h3q.425 0 .713.288T7 10v1.75q0 .425-.288.713T6 12.75H4.5v.75H7V15zm1.5-7V3.5H3V2h3v6zM9 19v-2h12v2zm0-6v-2h12v2zm0-6V5h12v2z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="cq-editor-btn" @click="insertChoiceImageUrl(i)" title="Insert image by URL">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M6.188 2h11.625A4.187 4.187 0 0 1 22 6.188v11.625A4.187 4.187 0 0 1 17.812 22H6.188A4.187 4.187 0 0 1 2 17.812V6.188A4.19 4.19 0 0 1 6.188 2m0 2C4.979 4 4 4.98 4 6.188v11.625C4 19.02 4.98 20 6.188 20h11.625C19.02 20 20 19.02 20 17.812V6.188C20 4.98 19.02 4 17.812 4z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M17.24 10.924a1.19 1.19 0 0 0-1.51-.013l-5.244 4.247a2.094 2.094 0 0 1-2.59.035l-1.385-1.06a.094.094 0 0 0-.122.007l-2.698 2.582a1 1 0 1 1-1.382-1.444l2.697-2.583a2.094 2.094 0 0 1 2.721-.15l1.385 1.06a.094.094 0 0 0 .116-.001l5.242-4.247a3.19 3.19 0 0 1 4.053.033l3.12 2.613a1 1 0 0 1-1.285 1.533z" clip-rule="evenodd"/><path d="M10.281 8.64a1.64 1.64 0 1 1-3.28 0a1.64 1.64 0 0 1 3.28 0"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="cq-editor-btn" @click="openChoiceUpload(i)" :disabled="uploadingImage" title="Upload image">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M19 13a1 1 0 0 0-1 1v.38l-1.48-1.48a2.79 2.79 0 0 0-3.93 0l-.7.7l-2.48-2.48a2.85 2.85 0 0 0-3.93 0L4 12.6V7a1 1 0 0 1 1-1h7a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-5a1 1 0 0 0-1-1M5 20a1 1 0 0 1-1-1v-3.57l2.9-2.9a.79.79 0 0 1 1.09 0l3.17 3.17l4.3 4.3Zm13-1a.9.9 0 0 1-.18.53L13.31 15l.7-.7a.77.77 0 0 1 1.1 0L18 17.21Zm4.71-14.71l-3-3a1 1 0 0 0-.33-.21a1 1 0 0 0-.76 0a1 1 0 0 0-.33.21l-3 3a1 1 0 0 0 1.42 1.42L18 4.41V10a1 1 0 0 0 2 0V4.41l1.29 1.3a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.42"/>
                                            </svg>
                                        </button>
                                        <input type="file"
                                               accept="image/*"
                                               class="hidden"
                                               :data-choice-upload="i"
                                               @change="uploadChoiceImage($event, i)">
                                    </div>

                                    <div class="px-4 py-2.5">
                                        <div contenteditable="true"
                                             :data-choice-editor="i"
                                             @input="syncChoiceEditor(i)"
                                             @blur="syncChoiceEditor(i)"
                                             class="cq-editor min-h-[3.5rem] text-sm text-gray-800 focus:outline-none"></div>
                                        <textarea class="hidden"
                                                  :name="'choices[' + i + '][text]'"
                                                  :data-choice-input="i"
                                                  x-model="choice.text"></textarea>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="removeChoice(i)"
                                x-show="choices.length > 2"
                                class="shrink-0 self-center text-gray-300 opacity-0 transition-all hover:text-red-400 focus:opacity-100 group-hover:opacity-100"
                                :class="richChoices ? 'mt-0.5' : ''"
                                title="Remove choice">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-3 flex items-center justify-between gap-3">
            <button type="button" @click="addChoice()"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-emerald-600 transition-colors hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add choice
            </button>

            <label for="rich-choices" class="inline-flex cursor-pointer items-center gap-3 text-sm text-gray-600">
                <input type="hidden" name="settings[rich_choices]" value="0">
                <input id="rich-choices" type="checkbox" name="settings[rich_choices]" value="1"
                       x-model="richChoices"
                       @change="onToggleRichChoices()"
                       class="sr-only">
                <span class="inline-flex items-center gap-2">
                    <span>Rich choices</span>
                    <button type="button"
                            class="cq-tooltip-anchor"
                            data-tooltip-placement="top"
                            data-tooltip-align="center"
                            data-tooltip="Use the rich editor for choices when you need formatting, links, or images inside answer options.">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 17.75a.75.75 0 0 0 .75-.75v-6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75M12 7a1 1 0 1 1 0 2a1 1 0 0 1 0-2"/>
                            <path fill-rule="evenodd" d="M1.25 12C1.25 6.063 6.063 1.25 12 1.25S22.75 6.063 22.75 12S17.937 22.75 12 22.75S1.25 17.937 1.25 12M12 2.75a9.25 9.25 0 1 0 0 18.5a9.25 9.25 0 0 0 0-18.5" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </span>
                <span class="relative inline-flex h-5 w-9 items-center rounded-full p-0.5 transition-colors duration-200"
                      :class="richChoices ? 'bg-emerald-500' : 'bg-gray-300'">
                    <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                          :class="richChoices ? 'translate-x-4' : 'translate-x-0'"></span>
                </span>
            </label>
        </div>
    </div>

    <div class="cq-card mb-4 px-6 py-5">
        <div class="mb-4 flex items-center gap-2">
            <p class="cq-form-section-title">Tag</p>
            <button type="button"
                    class="cq-tooltip-anchor"
                    data-tooltip-placement="right"
                    data-tooltip-align="center"
                    data-tooltip="Pick one of your existing tags or create a new one for this question.">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 17.75a.75.75 0 0 0 .75-.75v-6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75M12 7a1 1 0 1 1 0 2a1 1 0 0 1 0-2"/>
                    <path fill-rule="evenodd" d="M1.25 12C1.25 6.063 6.063 1.25 12 1.25S22.75 6.063 22.75 12S17.937 22.75 12 22.75S1.25 17.937 1.25 12M12 2.75a9.25 9.25 0 1 0 0 18.5a9.25 9.25 0 0 0 0-18.5" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>

        <div class="flex flex-col gap-2 md:flex-row">
            <select id="existing_tag" name="existing_tag" x-model="existingTag" @change="onTagChange()" class="cq-field text-sm md:max-w-sm">
                <option value="">Select tag</option>
                @foreach($tags as $tagOption)
                    <option value="{{ $tagOption }}">{{ $tagOption }}</option>
                @endforeach
                <option value="__new__">+ Add new tag</option>
            </select>
            <input type="text"
                   name="new_tag"
                   x-model="newTag"
                   x-show="existingTag === '__new__'"
                   x-cloak
                   placeholder="New tag"
                   class="cq-field text-sm md:max-w-sm">
        </div>
    </div>

    <details class="cq-card mb-4 px-6 py-5">
        <summary class="cq-form-section-title cursor-pointer">Feedback and Explanation</summary>

        <div class="mt-4 space-y-4">
            <div>
                <label for="feedback_correct" class="mb-1.5 block text-sm font-medium text-emerald-700">Feedback for correct answer</label>
                <textarea id="feedback_correct" name="feedback_correct" rows="3" class="cq-field border-emerald-200 bg-emerald-50/40 text-sm focus:border-emerald-400 focus:ring-emerald-100">{{ $feedbackCorrect }}</textarea>
            </div>

            <div>
                <label for="feedback_incorrect" class="mb-1.5 block text-sm font-medium text-red-700">Feedback for incorrect answer</label>
                <textarea id="feedback_incorrect" name="feedback_incorrect" rows="3" class="cq-field border-red-200 bg-red-50/40 text-sm focus:border-red-400 focus:ring-red-100">{{ $feedbackIncorrect }}</textarea>
            </div>

            <div>
                <label for="explanation" class="mb-1.5 block text-sm font-medium text-gray-700">Correct answer explanation</label>
                <textarea id="explanation" name="explanation" rows="4" class="cq-field text-sm">{{ $explanation }}</textarea>
            </div>
        </div>
    </details>

    <div class="sticky bottom-4 z-20 mt-6 rounded-2xl bg-white/25 px-3.5 py-2.5 shadow-[0_16px_40px_rgba(15,23,42,0.10)] backdrop-blur-md">
        <div class="flex items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <button type="button"
                        class="cq-btn cq-btn-secondary shrink-0"
                        data-tooltip-placement="top"
                        data-tooltip-align="start"
                        data-tooltip-max-width="16rem"
                        data-tooltip="Preview how this question will appear in tests."
                        @click="openPreview()">
                    Preview
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('admin.quizzes.questions.index', $quiz) }}"
                   class="{{ $isEdit ? 'inline-flex' : 'hidden sm:inline-flex' }} cq-btn cq-btn-secondary">
                    Cancel
                </a>
                @unless($isEdit)
                    <div class="flex overflow-hidden rounded-lg bg-emerald-600 shadow-sm sm:hidden">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            {{ $submitLabel }}
                        </button>
                        <button type="submit"
                                name="save_and_add_another"
                                value="1"
                                class="inline-flex items-center justify-center border-l border-emerald-400/60 px-3 text-white transition-colors hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                title="Save and add another">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 5v14m7-7H5"/>
                            </svg>
                        </button>
                    </div>
                    <button type="submit" name="save_and_add_another" value="1" class="hidden sm:inline-flex cq-btn cq-btn-secondary">
                        Save and add another
                    </button>
                @endunless
                <button type="submit" class="{{ $isEdit ? 'inline-flex' : 'hidden sm:inline-flex' }} cq-btn cq-btn-primary">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>

    <x-modal name="question-preview" maxWidth="2xl" focusable>
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Preview</h2>
        </div>

        <div class="max-h-[calc(100vh-13rem)] overflow-y-auto bg-gray-50 px-6 py-6">
            <div class="cq-question-card">
                <div class="grid grid-cols-[3rem_minmax(0,1fr)] gap-x-4 gap-y-3">
                    <div class="flex justify-center pt-1">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-base font-semibold text-white shadow-sm">1</span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-start justify-between gap-4">
                            <div class="cq-richtext min-w-0 pt-2.5 text-base font-medium text-gray-800" x-html="previewText()"></div>
                            <span class="cq-question-points" x-text="previewPoints()"></span>
                        </div>
                    </div>

                    <div></div>

                    <div class="min-w-0">
                        <template x-if="type === 'mcq_single' || type === 'tf'">
                            <div class="space-y-2">
                                <template x-for="(choice, i) in previewChoices()" :key="'single-' + i">
                                    <label class="cq-question-choice cursor-default">
                                        <input type="radio"
                                               name="preview-single"
                                               :checked="previewSelectedSingle === i"
                                               @change="previewSelectedSingle = i"
                                               class="cq-question-choice-input">
                                        <span class="cq-richtext text-sm text-gray-700" x-html="choice.text"></span>
                                    </label>
                                </template>
                            </div>
                        </template>

                        <template x-if="type === 'mcq_multi'">
                            <div>
                                <p class="mb-2 text-xs text-emerald-600">Select all that apply.</p>
                                <div class="space-y-2">
                                    <template x-for="(choice, i) in previewChoices()" :key="'multi-' + i">
                                        <label class="cq-question-choice cursor-default">
                                            <input type="checkbox"
                                                   :checked="previewSelectedMulti.includes(i)"
                                                   @change="togglePreviewMulti(i)"
                                                   class="cq-question-choice-input">
                                            <span class="cq-richtext text-sm text-gray-700" x-html="choice.text"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-gray-100 px-6 py-4">
            <button type="button" class="cq-btn cq-btn-secondary" @click="$dispatch('close-modal', 'question-preview')">
                Close
            </button>
        </div>
    </x-modal>
</form>
</div>

<script>
window.questionForm = function questionForm(config) {
    return {
        type: config.type,
        text: config.text,
        points: config.points,
        choices: (config.choices || []).map((choice, index) => ({
            text: choice.text ?? '',
            is_correct: !!choice.is_correct,
            sort_order: choice.sort_order ?? index,
        })),
        tfCorrect: config.tfCorrect || 'true',
        richChoices: !!config.richChoices,
        existingTag: config.existingTag ?? '',
        newTag: config.newTag ?? '',
        uploadUrl: config.uploadUrl,
        csrf: config.csrf,
        uploadingImage: false,
        showFormNotice: true,
        previewSelectedSingle: null,
        previewSelectedMulti: [],
        previewChoiceOrder: [],
        init() {
            this.$refs.questionEditor.innerHTML = this.text || '<p></p>';
            this.syncQuestionEditor();
            this.$nextTick(() => this.syncChoiceEditorsFromState());
        },
        syncAllEditors() {
            this.syncQuestionEditor();
            this.syncChoiceEditorsToState();
        },
        setType(nextType) {
            const previousType = this.type;
            this.type = nextType;

            if (
                (previousType === 'mcq_single' && nextType === 'mcq_multi') ||
                (previousType === 'mcq_multi' && nextType === 'mcq_single')
            ) {
                this.choices.forEach((choice) => {
                    choice.is_correct = false;
                });
            }
        },
        onToggleRichChoices() {
            this.$nextTick(() => this.syncChoiceEditorsFromState());
        },
        onTagChange() {
            if (this.existingTag !== '__new__') {
                this.newTag = '';
            }
        },
        syncQuestionEditor() {
            this.text = this.$refs.questionEditor.innerHTML.trim();
            this.$refs.textInput.value = this.text;
        },
        focusQuestionEditor() {
            this.$refs.questionEditor.focus();
        },
        formatQuestion(command, value = null) {
            this.focusQuestionEditor();
            document.execCommand(command, false, value);
            this.syncQuestionEditor();
        },
        insertQuestionLink() {
            const url = window.prompt('Enter a link URL');

            if (!url) return;

            this.formatQuestion('createLink', url);
        },
        insertQuestionImageUrl() {
            const url = window.prompt('Enter an image URL');

            if (!url) return;

            this.insertQuestionImage(url);
        },
        insertQuestionImage(url) {
            this.focusQuestionEditor();
            document.execCommand('insertImage', false, url);
            this.syncQuestionEditor();
        },
        async uploadQuestionImage(event) {
            const url = await this.uploadImage(event.target.files?.[0]);

            if (url) {
                this.insertQuestionImage(url);
            }

            event.target.value = '';
        },
        getChoiceEditor(index) {
            return this.$root.querySelector(`[data-choice-editor="${index}"]`);
        },
        getChoiceInput(index) {
            return this.$root.querySelector(`[data-choice-input="${index}"]`);
        },
        focusChoiceEditor(index) {
            const editor = this.getChoiceEditor(index);
            editor?.focus();
        },
        syncChoiceEditorsFromState() {
            if (!this.richChoices) {
                return;
            }

            this.choices.forEach((choice, index) => {
                const editor = this.getChoiceEditor(index);

                if (editor) {
                    editor.innerHTML = choice.text || '<p></p>';
                }
            });
        },
        syncChoiceEditorsToState() {
            if (!this.richChoices) {
                return;
            }

            this.choices.forEach((choice, index) => {
                const editor = this.getChoiceEditor(index);

                if (editor) {
                    choice.text = editor.innerHTML.trim();
                    const input = this.getChoiceInput(index);

                    if (input) {
                        input.value = choice.text;
                    }
                }
            });
        },
        syncChoiceEditor(index) {
            if (!this.richChoices) {
                return;
            }

            const editor = this.getChoiceEditor(index);
            const input = this.getChoiceInput(index);

            if (!editor || !input) {
                return;
            }

            this.choices[index].text = editor.innerHTML.trim();
            input.value = this.choices[index].text;
        },
        formatChoice(index, command, value = null) {
            this.focusChoiceEditor(index);
            document.execCommand(command, false, value);
            this.syncChoiceEditor(index);
        },
        insertChoiceLink(index) {
            const url = window.prompt('Enter a link URL');

            if (!url) return;

            this.formatChoice(index, 'createLink', url);
        },
        insertChoiceImageUrl(index) {
            const url = window.prompt('Enter an image URL');

            if (!url) return;

            this.insertChoiceImage(index, url);
        },
        insertChoiceImage(index, url) {
            this.focusChoiceEditor(index);
            document.execCommand('insertImage', false, url);
            this.syncChoiceEditor(index);
        },
        openChoiceUpload(index) {
            this.$root.querySelector(`[data-choice-upload="${index}"]`)?.click();
        },
        async uploadChoiceImage(event, index) {
            const url = await this.uploadImage(event.target.files?.[0]);

            if (url) {
                this.insertChoiceImage(index, url);
            }

            event.target.value = '';
        },
        async uploadImage(file) {
            if (!file) {
                return null;
            }

            this.uploadingImage = true;

            try {
                const formData = new FormData();
                formData.append('image', file);

                const response = await fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error('Image upload failed.');
                }

                const payload = await response.json();
                return payload.url;
            } catch (error) {
                window.alert('Unable to upload that image right now.');
                return null;
            } finally {
                this.uploadingImage = false;
            }
        },
        addChoice() {
            this.choices.push({ text: '', is_correct: false, sort_order: this.choices.length });

            this.$nextTick(() => {
                if (this.richChoices) {
                    this.syncChoiceEditorsFromState();
                    this.focusChoiceEditor(this.choices.length - 1);
                } else {
                    const inputs = this.$root.querySelectorAll('[data-choice-input]');
                    inputs[inputs.length - 1]?.focus();
                }
            });
        },
        removeChoice(index) {
            if (this.choices.length <= 2) {
                return;
            }

            const wasCorrect = this.choices[index]?.is_correct;
            this.choices.splice(index, 1);

            if (this.type === 'mcq_single' && wasCorrect && this.choices.length) {
                this.markSingleCorrect(0);
            }

            this.$nextTick(() => this.syncChoiceEditorsFromState());
        },
        markSingleCorrect(index) {
            this.choices.forEach((choice, choiceIndex) => {
                choice.is_correct = choiceIndex === index;
            });
        },
        openPreview() {
            this.syncAllEditors();
            this.previewSelectedSingle = null;
            this.previewSelectedMulti = [];
            this.previewChoiceOrder = this.buildPreviewChoices();
            this.$dispatch('open-modal', 'question-preview');
        },
        buildPreviewChoices() {
            if (this.type === 'tf') {
                return [{ text: 'True' }, { text: 'False' }];
            }

            const choices = this.choices.filter((choice) => this.stripHtml(choice.text).trim() !== '');
            const shuffleEnabled = !!this.$root.querySelector('#randomize')?.checked;

            if (!shuffleEnabled) {
                return choices;
            }

            const shuffled = [...choices];

            for (let i = shuffled.length - 1; i > 0; i -= 1) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }

            return shuffled;
        },
        previewChoices() {
            if (this.previewChoiceOrder.length) {
                return this.previewChoiceOrder;
            }

            return this.buildPreviewChoices();
        },
        previewText() {
            return this.text && this.text.trim() !== '' ? this.text : '<p></p>';
        },
        previewPoints() {
            const points = this.points || '0';
            return `${points} pt${Number(points) === 1 ? '' : 's'}`;
        },
        stripHtml(html) {
            const temp = document.createElement('div');
            temp.innerHTML = html || '';
            return temp.textContent || temp.innerText || '';
        },
        togglePreviewMulti(index) {
            if (this.previewSelectedMulti.includes(index)) {
                this.previewSelectedMulti = this.previewSelectedMulti.filter((value) => value !== index);
                return;
            }

            this.previewSelectedMulti = [...this.previewSelectedMulti, index];
        },
    };
};
</script>
