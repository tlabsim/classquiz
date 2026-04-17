@php
    $isEdit = isset($quiz);
@endphp

<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="cq-page-title">{{ $title }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $isEdit ? 'Refine the quiz details and keep the student-facing setup tidy.' : 'Create the shell first, then build questions and assignments around it.' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($isEdit)
                <a href="{{ route('admin.quizzes.show', $quiz) }}" class="cq-btn-secondary cq-btn-sm">
                    View quiz
                </a>
            @endif
            <a href="{{ route('admin.quizzes.index') }}" class="cq-btn-secondary cq-btn-sm">
                Back to quizzes
            </a>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <div class="cq-card px-6 py-6">
            <div class="grid gap-5">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">
                        Quiz title <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="title"
                           value="{{ old('title', $quiz->title ?? '') }}"
                           class="cq-field @error('title') cq-field-error @enderror"
                           placeholder="Intro to Biology - Chapter 3">
                    @error('title')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description"
                              rows="6"
                              class="cq-field @error('description') cq-field-error @enderror"
                              placeholder="Optional notes for teachers about this quiz, its scope, or how it should be used.">{{ old('description', $quiz->description ?? '') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        @if($isEdit)
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="cq-card px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Questions</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $quiz->questions_count }}</p>
                </div>
                <div class="cq-card px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Assignments</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $quiz->assignments_count }}</p>
                </div>
                <div class="cq-card px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Export</p>
                    <a href="{{ route('admin.quizzes.export', $quiz) }}" class="mt-2 inline-flex text-sm font-medium text-emerald-600 hover:text-emerald-700">
                        Download quiz JSON
                    </a>
                </div>
            </div>
        @endif

        <div class="sticky bottom-4 z-20 rounded-2xl bg-white/80 px-4 py-3 shadow-[0_16px_40px_rgba(15,23,42,0.10)] backdrop-blur-md">
            <div class="flex items-center justify-end gap-2">
                @if($isEdit)
                    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="cq-btn-secondary">Cancel</a>
                    <button type="submit" class="cq-btn-primary">Save changes</button>
                @else
                    <a href="{{ route('admin.quizzes.index') }}" class="cq-btn-secondary">Cancel</a>
                    <button type="submit" class="cq-btn-primary">Create quiz</button>
                @endif
            </div>
        </div>
    </form>
</div>
