@extends('layouts.admin')

@section('title', 'Questions')

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-48">{{ $quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700">Questions</span>
@endsection

@section('content')
@php
    $questionTypeLabels = [
        'tf' => 'True / False',
        'mcq_single' => 'MCQ-Single',
        'mcq_multi' => 'MCQ-Multi',
    ];
@endphp

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="cq-page-title">Questions</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ trans_choice(':count question|:count questions', $questions->count(), ['count' => $questions->count()]) }}
            &middot;
            {{ $questions->where('is_enabled', true)->count() }} enabled
        </p>
    </div>
    <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="cq-btn-primary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add question
    </a>
</div>

<div
    x-data="{
        search: '',
        showDisabled: true,
        typeMenuOpen: false,
        selectedTypes: [],
        deleteModalOpen: false,
        copyModalOpen: false,
        previewModalOpen: false,
        deleteAction: '',
        deleteLabel: '',
        copyAction: '',
        copyLabel: '',
        targetQuizId: '',
        previewQuestion: null,
        copyTargets: @js($copyTargets->map(fn ($quizItem) => ['id' => (string) $quizItem->id, 'title' => $quizItem->title])->values()),
        typeOptions: @js($questionTypeLabels),
        matchesType(type) {
            return this.selectedTypes.length === 0 || this.selectedTypes.includes(type);
        },
        toggleType(type) {
            if (this.selectedTypes.includes(type)) {
                this.selectedTypes = this.selectedTypes.filter((value) => value !== type);
                return;
            }

            this.selectedTypes = [...this.selectedTypes, type];
        },
        clearTypes() {
            this.selectedTypes = [];
        },
        selectedTypeLabel() {
            if (this.selectedTypes.length === 0) {
                return 'All types';
            }

            if (this.selectedTypes.length === 1) {
                return this.typeOptions[this.selectedTypes[0]] ?? this.selectedTypes[0];
            }

            return `${this.selectedTypes.length} types`;
        },
        openDelete(action, label) {
            this.deleteAction = action;
            this.deleteLabel = label;
            this.deleteModalOpen = true;
        },
        openCopyModal(action, label) {
            this.copyAction = action;
            this.copyLabel = label;
            this.targetQuizId = this.copyTargets[0]?.id ?? '';
            this.copyModalOpen = true;
        },
        openPreviewModal(question) {
            this.previewQuestion = question;
            this.previewModalOpen = true;
        },
    }"
    class="space-y-4"
>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1 basis-full">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Search questions..."
                   class="cq-field pl-9 text-sm">
        </div>

        <div class="flex w-full items-stretch gap-3 sm:w-auto sm:flex-nowrap">
            <div class="relative min-w-0 flex-1 sm:min-w-48 sm:flex-none" @keydown.escape.window="typeMenuOpen = false">
                <button type="button"
                        @click="typeMenuOpen = !typeMenuOpen"
                        class="cq-field flex h-full w-full items-center justify-between gap-3 text-left text-sm">
                    <span x-text="selectedTypeLabel()"></span>
                    <svg class="h-4 w-4 text-gray-400 transition-transform" :class="typeMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="typeMenuOpen"
                     x-cloak
                     @click.outside="typeMenuOpen = false"
                     class="absolute right-0 z-20 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                    <button type="button"
                            @click="clearTypes(); typeMenuOpen = false"
                            class="flex w-full items-center justify-between px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50">
                        <span>All types</span>
                        <svg x-show="selectedTypes.length === 0" class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>

                    <div class="border-t border-gray-100 py-1">
                        @foreach($questionTypeLabels as $typeValue => $typeLabel)
                            <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50">
                                <input type="checkbox"
                                       :checked="selectedTypes.includes('{{ $typeValue }}')"
                                       @change="toggleType('{{ $typeValue }}')"
                                       class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span>{{ $typeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="cq-field flex min-w-0 flex-1 items-center justify-between gap-4 sm:flex-none">
                <span class="text-sm text-gray-600">Show disabled</span>
                <button type="button"
                        @click="showDisabled = !showDisabled"
                        class="inline-flex items-center">
                    <span class="relative inline-flex h-5 w-9 items-center rounded-full p-0.5 transition-colors duration-200"
                          :class="showDisabled ? 'bg-emerald-500' : 'bg-gray-300'">
                        <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                              :class="showDisabled ? 'translate-x-4' : 'translate-x-0'"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div class="space-y-3" id="question-list">
        @forelse($questions as $question)
            @php
                $typeColors = [
                    'tf' => 'cq-badge-yellow',
                    'mcq_single' => 'cq-badge-blue',
                    'mcq_multi' => 'cq-badge-blue',
                ];

                $questionLabel = \Illuminate\Support\Str::limit(strip_tags($question->text), 100, '...');
                $previewPayload = [
                    'type' => $question->type,
                    'text' => $question->text,
                    'points' => $question->points,
                    'tag' => $question->tag,
                    'choices' => $question->choices->map(fn ($choice) => [
                        'text' => $choice->text,
                        'is_correct' => (bool) $choice->is_correct,
                    ])->values()->all(),
                ];
            @endphp

            <div class="cq-card group px-5 py-4 transition-opacity"
                 data-id="{{ $question->id }}"
                 data-type="{{ $question->type }}"
                 data-enabled="{{ $question->is_enabled ? '1' : '0' }}"
                 x-show="
                    matchesType('{{ $question->type }}') &&
                    (showDisabled || '{{ $question->is_enabled ? '1' : '0' }}' === '1') &&
                    ('{{ strtolower(addslashes(strip_tags($question->text))) }}'.includes(search.toLowerCase()))
                 "
                 :class="{
                    'opacity-55': !showDisabled && '{{ $question->is_enabled ? '1' : '0' }}' === '0',
                    'bg-gray-200 border-gray-300': '{{ $question->is_enabled ? '1' : '0' }}' === '0'
                 }">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <div class="flex min-w-0 flex-1 items-start gap-4">
                    <div class="drag-handle pt-1 shrink-0 cursor-grab text-gray-300 hover:text-gray-400" title="Drag to reorder">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                        </svg>
                    </div>

                    <div class="pt-1 text-sm font-semibold text-gray-400">
                        {{ $loop->iteration }}.
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="mb-2 flex min-h-6 flex-wrap items-center gap-2">
                            <span class="{{ $typeColors[$question->type] ?? 'cq-badge-gray' }}">
                                {{ $questionTypeLabels[$question->type] ?? $question->type }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $question->points }} pt{{ $question->points != 1 ? 's' : '' }}</span>
                            @if($question->tag)
                                <span class="cq-badge-gray">{{ $question->tag }}</span>
                            @endif
                        </div>

                        <p class="text-base leading-7 text-gray-800">{{ \Illuminate\Support\Str::limit(strip_tags($question->text), 260, '...') }}</p>

                        @if($question->choices->count())
                            <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5">
                                @foreach($question->choices as $choice)
                                    <li class="flex items-center gap-1 text-xs {{ $choice->is_correct ? 'font-medium text-emerald-700' : 'text-gray-400' }}">
                                        @if($choice->is_correct)
                                            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <span class="flex h-3 w-3 shrink-0 items-center justify-center">
                                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-300"></span>
                                            </span>
                                        @endif
                                        {{ \Illuminate\Support\Str::limit(strip_tags($choice->text), 80, '...') }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    </div>

                    <div class="flex w-full items-center justify-between gap-3 opacity-100 transition-opacity sm:w-auto sm:justify-end sm:opacity-0 sm:group-hover:opacity-100">
                        <div class="flex h-9 items-center overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <button type="button"
                                    @click="openPreviewModal(@js($previewPayload))"
                                    class="inline-flex h-9 w-10 items-center justify-center text-gray-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                    title="Preview">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <g>
                                        <path stroke-linecap="round" stroke-width="1.5" d="M20.998 12.503s.002-.47.002-1c0-4.48 0-6.72-1.391-8.111S15.979 2 11.5 2C7.022 2 4.782 2 3.391 3.392S2 7.023 2 11.502c0 4.48 0 6.72 1.391 8.112C4.558 20.781 6.321 20.97 9.5 21"/>
                                        <path stroke-linejoin="round" stroke-width="1.5" d="M2 7h19"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 16h1m3-4h5m-9 0h1"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 18.5h.009"/>
                                        <path stroke-width="1.5" d="M21.772 18.023c.152.213.228.32.228.477c0 .158-.076.264-.228.477C21.089 19.935 19.345 22 17 22s-4.089-2.065-4.772-3.023c-.152-.213-.228-.32-.228-.477c0-.158.076-.264.228-.477C12.911 17.065 14.655 15 17 15s4.089 2.065 4.772 3.023Z"/>
                                    </g>
                                </svg>
                            </button>

                            <a href="{{ route('admin.quizzes.questions.edit', [$quiz, $question]) }}"
                               class="inline-flex h-9 w-10 items-center justify-center border-l border-gray-200 text-gray-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                               title="Edit">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <g stroke-linejoin="round" stroke-width="1.5">
                                        <path d="m16.425 4.605l.99-.99a2.1 2.1 0 0 1 2.97 2.97l-.99.99m-2.97-2.97l-6.66 6.66a3.96 3.96 0 0 0-1.041 1.84L8 16l2.896-.724a3.96 3.96 0 0 0 1.84-1.042l6.659-6.659m-2.97-2.97l2.97 2.97"/>
                                        <path stroke-linecap="round" d="M19 13.5c0 3.288 0 4.931-.908 6.038a4 4 0 0 1-.554.554C16.43 21 14.788 21 11.5 21H11c-3.771 0-5.657 0-6.828-1.172S3 16.771 3 13v-.5c0-3.287 0-4.931.908-6.038q.25-.304.554-.554C5.57 5 7.212 5 10.5 5"/>
                                    </g>
                                </svg>
                            </a>

                            <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                                <button type="button"
                                        @click="open = false; openCopyModal('{{ route('admin.quizzes.questions.copy', [$quiz, $question]) }}', '{{ addslashes($questionLabel) }}')"
                                        class="inline-flex h-9 w-10 items-center justify-center border-l border-gray-200 text-gray-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                        title="Copy to another quiz">
                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <g stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                                            <path d="M9 15c0-2.828 0-4.243.879-5.121C10.757 9 12.172 9 15 9h1c2.828 0 4.243 0 5.121.879C22 10.757 22 12.172 22 15v1c0 2.828 0 4.243-.879 5.121C20.243 22 18.828 22 16 22h-1c-2.828 0-4.243 0-5.121-.879C9 20.243 9 18.828 9 16z"/>
                                            <path d="M17 9c-.003-2.957-.047-4.489-.908-5.538a4 4 0 0 0-.554-.554C14.43 2 12.788 2 9.5 2c-3.287 0-4.931 0-6.038.908a4 4 0 0 0-.554.554C2 4.57 2 6.212 2 9.5c0 3.287 0 4.931.908 6.038a4 4 0 0 0 .554.554c1.05.86 2.58.906 5.538.908"/>
                                        </g>
                                    </svg>
                                </button>
                            </div>

                            <button type="button"
                                    @click="openDelete('{{ route('admin.quizzes.questions.destroy', [$quiz, $question]) }}', '{{ addslashes($questionLabel) }}')"
                                    class="inline-flex h-9 w-10 items-center justify-center border-l border-gray-200 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                    title="Delete">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-width="1.5" d="m19.5 5.5l-.62 10.025c-.158 2.561-.237 3.842-.88 4.763a4 4 0 0 1-1.2 1.128c-.957.584-2.24.584-4.806.584c-2.57 0-3.855 0-4.814-.585a4 4 0 0 1-1.2-1.13c-.642-.922-.72-2.205-.874-4.77L4.5 5.5M3 5.5h18m-4.944 0l-.683-1.408c-.453-.936-.68-1.403-1.071-1.695a2 2 0 0 0-.275-.172C13.594 2 13.074 2 12.035 2c-1.066 0-1.599 0-2.04.234a2 2 0 0 0-.278.18c-.395.303-.616.788-1.058 1.757L8.053 5.5m1.447 11v-6m5 6v-6"/>
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('admin.quizzes.questions.toggle', [$quiz, $question]) }}" class="ml-1">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 shadow-sm transition-colors hover:border-gray-300">
                                <span class="relative inline-flex h-5 w-9 items-center rounded-full p-0.5 transition-colors duration-200 {{ $question->is_enabled ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                    <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200 {{ $question->is_enabled ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="cq-card px-6 py-12 text-center">
                <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="mb-3 text-sm text-gray-500">No questions yet.</p>
                <a href="{{ route('admin.quizzes.questions.create', $quiz) }}" class="cq-btn-primary cq-btn-sm">Add the first question</a>
            </div>
        @endforelse
    </div>

    <div x-show="deleteModalOpen"
         x-cloak
         class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 px-4">
        <div @click.outside="deleteModalOpen = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h2 class="text-lg font-semibold text-gray-900">Delete question?</h2>
            <p class="mt-2 text-sm text-gray-600">This action cannot be undone.</p>
            <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700" x-text="deleteLabel"></p>

            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="deleteModalOpen = false" class="cq-btn cq-btn-secondary">Cancel</button>
                <form :action="deleteAction" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="cq-btn cq-btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <div x-show="copyModalOpen"
         x-cloak
         class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 px-4">
        <div @click.outside="copyModalOpen = false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h2 class="text-lg font-semibold text-gray-900">Copy question</h2>
            <p class="mt-2 text-sm text-gray-600">Choose a different quiz to copy this question into.</p>
            <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700" x-text="copyLabel"></p>

            <template x-if="copyTargets.length > 0">
                <div class="mt-4">
                    <label for="target_quiz_id" class="mb-1.5 block text-sm font-medium text-gray-700">Target quiz</label>
                    <select id="target_quiz_id" x-model="targetQuizId" class="cq-field text-sm">
                        <template x-for="quizOption in copyTargets" :key="quizOption.id">
                            <option :value="quizOption.id" x-text="quizOption.title"></option>
                        </template>
                    </select>
                </div>
            </template>

            <template x-if="copyTargets.length === 0">
                <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    No other quizzes are available to copy into.
                </p>
            </template>

            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <button type="button" @click="copyModalOpen = false" class="cq-btn cq-btn-secondary">Cancel</button>
                <form :action="copyAction" method="POST" x-show="copyTargets.length > 0">
                    @csrf
                    <input type="hidden" name="target_quiz_id" :value="targetQuizId">
                    <button type="submit" class="cq-btn cq-btn-primary">Copy question</button>
                </form>
            </div>
        </div>
    </div>

    <div x-show="previewModalOpen"
         x-cloak
         class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/40 px-4">
        <div @click.outside="previewModalOpen = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-xl">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Question preview</h2>
            </div>

            <div class="bg-gray-50 px-6 py-6" x-show="previewQuestion">
                <div class="cq-question-card">
                    <div class="cq-question-header">
                        <div class="flex items-start gap-2">
                            <span class="cq-question-index">1.</span>
                            <div class="cq-richtext text-base font-medium text-gray-800" x-html="previewQuestion?.text ?? ''"></div>
                        </div>
                        <span class="cq-question-points" x-text="previewQuestion ? `${previewQuestion.points} pt${Number(previewQuestion.points) === 1 ? '' : 's'}` : ''"></span>
                    </div>

                    <div class="mb-3 flex flex-wrap items-center gap-2" x-show="previewQuestion?.tag">
                        <span class="cq-badge-gray" x-text="previewQuestion?.tag"></span>
                    </div>

                    <template x-if="previewQuestion?.type === 'mcq_multi'">
                        <div class="mb-2 text-xs text-emerald-600">Select all that apply.</div>
                    </template>

                    <div class="space-y-2">
                        <template x-for="(choice, index) in (previewQuestion?.choices ?? [])" :key="index">
                            <div class="cq-question-choice" :class="choice.is_correct ? 'bg-emerald-50' : ''">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                                    <template x-if="choice.is_correct">
                                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </template>
                                    <template x-if="!choice.is_correct">
                                        <span class="inline-block h-2 w-2 rounded-full bg-gray-300"></span>
                                    </template>
                                </span>
                                <div class="cq-richtext text-sm text-gray-700" x-html="choice.text"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 px-6 py-4">
                <button type="button" @click="previewModalOpen = false" class="cq-btn cq-btn-secondary">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="cq-btn-secondary cq-btn-sm">
        Quiz settings
    </a>
    <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="cq-btn-secondary cq-btn-sm">
        Assignments &rarr;
    </a>
</div>

@if($questions->count() > 1)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('question-list');
    let dragging = null;

    list.querySelectorAll('.drag-handle').forEach((handle) => {
        const card = handle.closest('[data-type]');
        card.setAttribute('draggable', 'true');
        card.addEventListener('dragstart', () => {
            dragging = card;
            card.classList.add('opacity-50');
        });
        card.addEventListener('dragend', () => {
            dragging = null;
            card.classList.remove('opacity-50');
            saveOrder();
        });
    });

    list.addEventListener('dragover', (event) => {
        event.preventDefault();
        const target = event.target.closest('[data-type]');

        if (target && target !== dragging) {
            const rect = target.getBoundingClientRect();
            const after = event.clientY > rect.top + (rect.height / 2);
            list.insertBefore(dragging, after ? target.nextSibling : target);
        }
    });

    function saveOrder() {
        const order = [...list.querySelectorAll('[data-type]')].map((element) => element.dataset.id ?? null).filter(Boolean);

        if (!order.length) return;

        fetch('{{ route('admin.quizzes.questions.reorder', $quiz) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ order }),
        });
    }
});
</script>
@endif

@endsection
