@php
    $assignmentModel = $assignment ?? null;
    $isEdit = $assignmentModel !== null;
    $assignmentTitle = old('title', $assignmentModel?->title ?? '');
    $assignmentInstructions = old('instructions', $assignmentModel?->instructions ?? '');
    $assignmentTimezone = old('settings.timezone', $assignmentModel?->timezone() ?? auth()->user()?->timezone() ?? config('app.timezone', 'UTC'));
    $availabilityStart = old('availability_start', $assignmentModel?->availability_start?->copy()->timezone($assignmentTimezone)->format('Y-m-d H:i'));
    $availabilityEnd = old('availability_end', $assignmentModel?->availability_end?->copy()->timezone($assignmentTimezone)->format('Y-m-d H:i'));
    $accessCodeRequired = (bool) old('access_code_required', $assignmentModel?->access_code_required ?? true);
    $accessCodeStartsBeforeMinutes = old('access_code_starts_before_minutes', $assignmentModel?->access_code_starts_before_minutes ?? 15);
    $durationMinutes = old('duration_minutes', $assignmentModel?->duration_minutes ?? 30);
    $enabledQuestionCount = $quiz->questions()->where('is_enabled', true)->count();
    $enabledQuestionPoints = $quiz->questions()->where('is_enabled', true)->sum('points');
    $timezones = collect(timezone_identifiers_list())
        ->map(function ($timezone) {
            $offsetMinutes = now($timezone)->utcOffset();
            $sign = $offsetMinutes >= 0 ? '+' : '-';
            $absoluteMinutes = abs($offsetMinutes);
            $hours = intdiv($absoluteMinutes, 60);
            $minutes = $absoluteMinutes % 60;
            $offsetLabel = $minutes === 0
                ? "UTC{$sign}{$hours}"
                : sprintf('UTC%s%d:%02d', $sign, $hours, $minutes);

            return [
                'timezone' => $timezone,
                'offset' => $offsetMinutes,
                'label' => "{$timezone} ({$offsetLabel})",
            ];
        })
        ->sortBy([
            ['offset', 'asc'],
            ['timezone', 'asc'],
        ])
        ->mapWithKeys(fn ($item) => [$item['timezone'] => $item['label']])
        ->all();
    $settings = [
        'allow_resume' => (bool) old('settings.allow_resume', $assignmentModel?->setting('allow_resume') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['allow_resume']),
        'show_score' => (bool) old('settings.show_score', $assignmentModel?->setting('show_score') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['show_score']),
        'show_correct_answers' => (bool) old('settings.show_correct_answers', $assignmentModel?->setting('show_correct_answers') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['show_correct_answers']),
        'show_feedback_and_explanation' => (bool) old('settings.show_feedback_and_explanation', $assignmentModel?->setting('show_feedback_and_explanation') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['show_feedback_and_explanation']),
        'randomize_questions' => (bool) old('settings.randomize_questions', $assignmentModel?->setting('randomize_questions') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['randomize_questions']),
        'question_presentation' => old('settings.question_presentation', $assignmentModel?->setting('question_presentation') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['question_presentation']),
        'allow_modify_previous_answers' => (bool) old('settings.allow_modify_previous_answers', $assignmentModel?->setting('allow_modify_previous_answers') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['allow_modify_previous_answers']),
        'collect_email' => true,
        'collect_class_id' => (bool) old('settings.collect_class_id', $assignmentModel?->setting('collect_class_id') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['collect_class_id']),
        'collect_name' => (bool) old('settings.collect_name', $assignmentModel?->setting('collect_name') ?? \App\Models\QuizAssignment::SETTINGS_DEFAULTS['collect_name']),
        'timezone' => $assignmentTimezone,
        'max_attempts' => old('settings.max_attempts', $assignmentModel?->setting('max_attempts', 1) ?? 1),
    ];
    $isActive = (bool) old('is_active', $assignmentModel?->is_active ?? true);
@endphp

<div class="w-full"
     x-data="window.assignmentForm(@js([
         'instructions' => $assignmentInstructions,
         'accessCodeRequired' => $accessCodeRequired,
         'assignmentActive' => $isActive,
         'questionPresentation' => $settings['question_presentation'],
         'collectClassId' => $settings['collect_class_id'],
         'collectName' => $settings['collect_name'],
         'durationMinutes' => $durationMinutes,
         'availabilityStart' => $availabilityStart,
         'availabilityEnd' => $availabilityEnd,
         'assignmentTimezone' => $settings['timezone'],
         'showScore' => $settings['show_score'],
         'showCorrectAnswers' => $settings['show_correct_answers'],
         'showFeedbackAndExplanation' => $settings['show_feedback_and_explanation'],
     ]))"
     x-effect="if (!showScore) { showCorrectAnswers = false; showFeedbackAndExplanation = false; } else if (!showCorrectAnswers) { showFeedbackAndExplanation = false; }"
     x-init="init()">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="cq-page-title">{{ $title }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $isEdit ? 'Refine scheduling, access, and student-facing instructions.' : 'Set timing, rules, and the delivery experience for this quiz.' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($isEdit)
                <a href="{{ route('admin.quizzes.assignments.show', [$quiz, $assignmentModel]) }}" class="cq-btn-secondary cq-btn-sm">View assignment</a>
            @endif
            <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Back to assignments</a>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700">{{ $quiz->title }}</span>
            <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700">{{ $enabledQuestionCount }} {{ \Illuminate\Support\Str::plural('question', $enabledQuestionCount) }}</span>
            <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700">{{ rtrim(rtrim(number_format((float) $enabledQuestionPoints, 2, '.', ''), '0'), '.') }} pts.</span>
            <span x-show="durationBadge()" x-text="durationBadge()" class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700"></span>
            <span x-show="formattedDateBadge(availabilityStart, 'From')" x-text="formattedDateBadge(availabilityStart, 'From')" class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700"></span>
            <span x-show="formattedDateBadge(availabilityEnd, 'Until')" x-text="formattedDateBadge(availabilityEnd, 'Until')" class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700"></span>
        </div>
        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
              :class="assignmentActive ? 'border-emerald-200 bg-white text-emerald-700' : 'border-gray-200 bg-white text-gray-600'"
              x-text="assignmentActive ? 'Active assignment' : 'Inactive assignment'"></span>
    </div>

    <form method="POST" action="{{ $action }}" class="space-y-5" @submit="syncInstructions()">
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(18rem,0.75fr)]">
            <div class="space-y-5">
                <div class="cq-card px-6 py-6">
                    <div class="space-y-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Assignment title</label>
                            <input type="text"
                                   name="title"
                                   value="{{ $assignmentTitle }}"
                                   class="cq-field @error('title') cq-field-error @enderror"
                                   placeholder="Optional. Defaults to the quiz title if left blank.">
                            @error('title')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Instructions</label>
                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-gray-50 px-3 py-2">
                                    <button type="button" class="cq-editor-btn" @click="formatInstructions('bold')" title="Bold"><strong>B</strong></button>
                                    <button type="button" class="cq-editor-btn italic" @click="formatInstructions('italic')" title="Italic">I</button>
                                    <button type="button" class="cq-editor-btn underline" @click="formatInstructions('underline')" title="Underline">U</button>
                                    <span class="h-5 w-px bg-gray-200"></span>
                                    <button type="button" class="cq-editor-btn" @click="formatInstructions('insertUnorderedList')" title="Bulleted list">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="cq-editor-btn" @click="formatInstructions('insertOrderedList')" title="Numbered list">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M3 22v-1.5h2.5v-.75H4v-1.5h1.5v-.75H3V16h3q.425 0 .713.288T7 17v1q0 .425-.288.713T6 19q.425 0 .713.288T7 20v1q0 .425-.288.713T6 22zm0-7v-2.75q0-.425.288-.712T4 11.25h1.5v-.75H3V9h3q.425 0 .713.288T7 10v1.75q0 .425-.288.713T6 12.75H4.5v.75H7V15zm1.5-7V3.5H3V2h3v6zM9 19v-2h12v2zm0-6v-2h12v2zm0-6V5h12v2z"/>
                                        </svg>
                                    </button>
                                    <span class="h-5 w-px bg-gray-200"></span>
                                    <button type="button" class="cq-editor-btn" @click="insertInstructionsLink()" title="Insert link">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.415 4.84a4.775 4.775 0 0 1 6.752 6.752l-.013.013l-2.264 2.265a4.776 4.776 0 0 1-7.201-.516a1 1 0 0 1 1.601-1.198a2.774 2.774 0 0 0 4.185.3l2.259-2.259a2.776 2.776 0 0 0-3.925-3.923L12.516 7.56a1 1 0 0 1-1.41-1.418l1.298-1.291zM8.818 9.032a4.775 4.775 0 0 1 5.492 1.614a1 1 0 0 1-1.601 1.198a2.775 2.775 0 0 0-4.185-.3l-2.258 2.259a2.775 2.775 0 0 0 3.923 3.924l1.285-1.285a1 1 0 1 1 1.414 1.414l-1.291 1.291l-.012.013a4.775 4.775 0 0 1-6.752-6.752l.012-.013L7.11 10.13a4.8 4.8 0 0 1 1.708-1.098" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                                <div x-ref="instructionsEditor"
                                     contenteditable="true"
                                     @input="syncInstructions()"
                                     @blur="syncInstructions()"
                                     class="cq-editor min-h-40 px-4 py-3 text-sm text-gray-800 focus:outline-none"></div>
                            </div>
                            <textarea x-ref="instructionsInput" name="instructions" class="hidden" x-model="instructions"></textarea>
                            @error('instructions')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="cq-card px-6 py-6">
                    <div class="mb-4">
                        <h2 class="cq-section-title">Timing and Delivery</h2>
                        <p class="mt-1 text-sm text-gray-500">Control whether the assignment is live, how long students get, when it is available, and how access is granted.</p>
                    </div>

                    <div class="space-y-6">
                        <label class="flex items-start justify-between gap-4">
                            <span>
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" x-model="assignmentActive" class="sr-only" {{ $isActive ? 'checked' : '' }}>
                            <span>
                                <span class="block text-sm font-medium text-gray-800">Assignment is active</span>
                                <span class="mt-0.5 block text-sm text-gray-500">Students can only access an assignment if it is active and within the configured windows.</span>
                            </span>
                            </span>
                            <span class="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full p-0.5 transition-colors duration-200"
                                  :class="assignmentActive ? 'bg-emerald-500' : 'bg-gray-300'">
                                <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                                      :class="assignmentActive ? 'translate-x-4' : 'translate-x-0'"></span>
                            </span>
                        </label>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Duration</label>
                            <div class="flex items-center gap-3">
                                <input type="number"
                                       min="1"
                                       max="480"
                                       name="duration_minutes"
                                       x-model="durationMinutes"
                                       value="{{ $durationMinutes }}"
                                       class="cq-field w-32 @error('duration_minutes') cq-field-error @enderror">
                                <span class="text-sm text-gray-500">minutes</span>
                            </div>
                            <p class="mt-1.5 text-sm text-gray-500">Leave blank if the quiz should not have a time limit once a student starts.</p>
                            @error('duration_minutes')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Timezone</label>
                            <select name="settings[timezone]"
                                    x-model="assignmentTimezone"
                                    class="cq-field @error('settings.timezone') cq-field-error @enderror">
                                @foreach($timezones as $timezone => $label)
                                    <option value="{{ $timezone }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('settings.timezone')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Available from</label>
                            <input type="text" name="availability_start" x-model="availabilityStart" value="{{ $availabilityStart }}" data-flatpickr class="cq-field @error('availability_start') cq-field-error @enderror" placeholder="Select date and time">
                            @error('availability_start')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Available until</label>
                            <input type="text" name="availability_end" x-model="availabilityEnd" value="{{ $availabilityEnd }}" data-flatpickr class="cq-field @error('availability_end') cq-field-error @enderror" placeholder="Select date and time">
                            @error('availability_end')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="flex items-start justify-between gap-4">
                                <span>
                                <input type="hidden" name="access_code_required" value="0">
                                <input type="checkbox" name="access_code_required" value="1" x-model="accessCodeRequired" class="sr-only">
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Access code required</span>
                                    <span class="mt-0.5 block text-sm text-gray-500">Require students to collect an emailed access code before they can begin the quiz.</span>
                                </span>
                                </span>
                                <span class="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full p-0.5 transition-colors duration-200"
                                      :class="accessCodeRequired ? 'bg-emerald-500' : 'bg-gray-300'">
                                    <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                                      :class="accessCodeRequired ? 'translate-x-4' : 'translate-x-0'"></span>
                                </span>
                            </label>
                        </div>

                        <div x-show="accessCodeRequired" x-cloak>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Access code can be acquired from</label>
                            <div class="flex items-center gap-3">
                                <input type="number"
                                       min="0"
                                       max="1440"
                                       name="access_code_starts_before_minutes"
                                       value="{{ $accessCodeStartsBeforeMinutes }}"
                                       class="cq-field w-28 @error('access_code_starts_before_minutes') cq-field-error @enderror">
                                <span class="text-sm text-gray-500">minutes before the quiz starts</span>
                            </div>
                            @error('access_code_starts_before_minutes')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="cq-card px-6 py-6">
                    <h2 class="cq-section-title">Student Experience</h2>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-xl border border-gray-100 px-4 py-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700">How questions appear</label>
                            <input type="hidden" name="settings[question_presentation]" x-model="questionPresentation">
                            <div class="space-y-2">
                                <label class="flex items-start gap-3 rounded-lg border border-gray-100 px-3 py-3">
                                    <input type="radio" value="one_per_page" x-model="questionPresentation" class="mt-1 h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-medium text-gray-800">One question per page</span>
                                        <span class="mt-0.5 block text-sm text-gray-500">Default. Students move through the quiz one question at a time.</span>
                                    </span>
                                </label>
                                <label class="flex items-start gap-3 rounded-lg border border-gray-100 px-3 py-3">
                                    <input type="radio" value="all_in_same_page" x-model="questionPresentation" class="mt-1 h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>
                                        <span class="block text-sm font-medium text-gray-800">All in same page</span>
                                        <span class="mt-0.5 block text-sm text-gray-500">Show the full quiz on one page with a single final submit.</span>
                                    </span>
                                </label>
                            </div>
                            @error('settings.question_presentation')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div x-show="questionPresentation === 'one_per_page'" x-cloak>
                            <label class="flex items-start gap-3 rounded-xl border border-gray-100 px-4 py-3">
                                <input type="hidden" name="settings[allow_modify_previous_answers]" value="0">
                                <input type="checkbox"
                                       name="settings[allow_modify_previous_answers]"
                                       value="1"
                                       {{ $settings['allow_modify_previous_answers'] ? 'checked' : '' }}
                                       class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span>
                                    <span class="block text-sm text-gray-700">Allow students to go back and modify previous answers</span>
                                </span>
                            </label>
                        </div>

                        <div class="rounded-xl border border-gray-100 px-4 py-4">
                            <h3 class="text-sm font-medium text-gray-800">What data to collect from students</h3>
                            <p class="mt-1 text-sm text-gray-500">Email is always collected. Name and Class ID can be enabled when needed.</p>
                            <div class="mt-4 space-y-3">
                                <label class="flex items-start justify-between gap-4 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 opacity-75">
                                    <span>
                                        <input type="hidden" name="settings[collect_email]" value="1">
                                        <span class="block text-sm font-medium text-gray-800">Email</span>
                                        <span class="mt-0.5 block text-sm text-gray-500">Always enabled for access code delivery, resume links, and result lookup.</span>
                                    </span>
                                    <span class="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-emerald-500 p-0.5">
                                        <span class="block h-4 w-4 translate-x-4 rounded-full bg-white shadow-sm"></span>
                                    </span>
                                </label>

                                @foreach([
                                    'collect_class_id' => [
                                        'label' => 'Class ID',
                                        'description' => 'Ask students for a class, section, or roll identifier during registration.',
                                    ],
                                    'collect_name' => [
                                        'label' => 'Name',
                                        'description' => 'Ask students for their full name during registration.',
                                    ],
                                ] as $key => $field)
                                    <label class="flex items-start justify-between gap-4 rounded-xl border border-gray-100 px-4 py-3">
                                        <span>
                                            <input type="hidden" name="settings[{{ $key }}]" value="0">
                                            <input type="checkbox"
                                                   name="settings[{{ $key }}]"
                                                   value="1"
                                                   x-model="{{ $key === 'collect_class_id' ? 'collectClassId' : 'collectName' }}"
                                                   class="sr-only">
                                            <span class="block text-sm font-medium text-gray-800">{{ $field['label'] }}</span>
                                            <span class="mt-0.5 block text-sm text-gray-500">{{ $field['description'] }}</span>
                                        </span>
                                        <span class="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full p-0.5 transition-colors duration-200"
                                              :class="{{ $key === 'collect_class_id' ? 'collectClassId' : 'collectName' }} ? 'bg-emerald-500' : 'bg-gray-300'">
                                            <span class="block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                                                  :class="{{ $key === 'collect_class_id' ? 'collectClassId' : 'collectName' }} ? 'translate-x-4' : 'translate-x-0'"></span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @foreach([
                            'allow_resume' => 'Allow students to resume after closing the browser',
                            'randomize_questions' => 'Randomize question order',
                        ] as $key => $label)
                            <label class="flex items-start gap-3 rounded-xl border border-gray-100 px-4 py-3">
                                <input type="hidden" name="settings[{{ $key }}]" value="0">
                                <input type="checkbox"
                                       name="settings[{{ $key }}]"
                                       value="1"
                                       {{ $settings[$key] ? 'checked' : '' }}
                                       class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach

                        <div class="rounded-xl border border-gray-100 px-4 py-3">
                            <label class="flex items-start gap-3">
                                <input type="hidden" name="settings[show_score]" value="0">
                                <input type="checkbox"
                                       name="settings[show_score]"
                                       value="1"
                                       x-model="showScore"
                                       class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">Show score after submission</span>
                            </label>

                            <div x-show="showScore" x-cloak class="mt-3 ml-7 space-y-3">
                                <label class="flex items-start gap-3">
                                    <input type="hidden" name="settings[show_correct_answers]" value="0">
                                    <input type="checkbox"
                                           name="settings[show_correct_answers]"
                                           value="1"
                                           x-model="showCorrectAnswers"
                                           class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm text-gray-700">Show correct answers</span>
                                </label>

                                <div x-show="showCorrectAnswers" x-cloak class="ml-7">
                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="settings[show_feedback_and_explanation]" value="0">
                                        <input type="checkbox"
                                               name="settings[show_feedback_and_explanation]"
                                               value="1"
                                               x-model="showFeedbackAndExplanation"
                                               class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        <span class="text-sm text-gray-700">Show feedback and explanation</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Maximum attempts</label>
                            <input type="number"
                                   min="1"
                                   max="10"
                                   name="settings[max_attempts]"
                                   value="{{ $settings['max_attempts'] }}"
                                   class="cq-field w-28 @error('settings.max_attempts') cq-field-error @enderror">
                            <p class="mt-1.5 text-sm text-gray-500">Limit how many submitted attempts the same student email can make for this assignment.</p>
                            @error('settings.max_attempts')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="sticky bottom-4 z-20 rounded-2xl bg-white/80 px-4 py-3 shadow-[0_16px_40px_rgba(15,23,42,0.10)] backdrop-blur-md">
            <div class="flex items-center justify-end gap-2">
                <a href="{{ $isEdit ? route('admin.quizzes.assignments.show', [$quiz, $assignmentModel]) : route('admin.quizzes.assignments.index', $quiz) }}" class="cq-btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="cq-btn-primary">
                    {{ $isEdit ? 'Save changes' : 'Create assignment' }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
window.assignmentForm = function assignmentForm(config) {
    return {
        instructions: config.instructions || '',
        accessCodeRequired: !!config.accessCodeRequired,
        assignmentActive: !!config.assignmentActive,
        questionPresentation: config.questionPresentation || 'one_per_page',
        collectClassId: !!config.collectClassId,
        collectName: !!config.collectName,
        durationMinutes: config.durationMinutes || '',
        availabilityStart: config.availabilityStart || '',
        availabilityEnd: config.availabilityEnd || '',
        assignmentTimezone: config.assignmentTimezone || 'UTC',
        showScore: !!config.showScore,
        showCorrectAnswers: !!config.showCorrectAnswers,
        showFeedbackAndExplanation: !!config.showFeedbackAndExplanation,
        init() {
            this.$refs.instructionsEditor.innerHTML = this.instructions || '<p></p>';
            this.syncInstructions();
        },
        durationBadge() {
            const minutes = String(this.durationMinutes ?? '').trim();
            if (!minutes) return '';

            const parsed = Number(minutes);
            if (!Number.isFinite(parsed) || parsed <= 0) return '';

            return `${parsed} min`;
        },
        formattedDateBadge(value, prefix) {
            const raw = String(value ?? '').trim();
            if (!raw) return '';

            const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{1,2}):(\d{2})$/);
            if (!match) return '';

            const [, year, month, day, hour, minute] = match;
            const hour24 = Number(hour);
            const minuteValue = Number(minute);

            if (!Number.isInteger(hour24) || hour24 < 0 || hour24 > 23 || !Number.isInteger(minuteValue) || minuteValue < 0 || minuteValue > 59) {
                return '';
            }

            const hour12 = hour24 % 12 || 12;
            const meridiem = hour24 >= 12 ? 'PM' : 'AM';
            const offset = this.timezoneOffsetLabel(raw);

            return `${prefix} ${year}-${month}-${day} ${String(hour12).padStart(2, '0')}:${minute} ${meridiem}${offset ? ` ${offset}` : ''}`;
        },
        timezoneOffsetLabel(value) {
            const raw = String(value ?? '').trim();
            if (!raw || !this.assignmentTimezone) return '';

            const normalized = raw.replace(' ', 'T');
            const parsed = new Date(`${normalized}:00Z`);

            if (Number.isNaN(parsed.getTime())) return '';

            try {
                const formatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: this.assignmentTimezone,
                    timeZoneName: 'shortOffset',
                });
                const offsetPart = formatter.formatToParts(parsed).find((part) => part.type === 'timeZoneName')?.value ?? '';

                return offsetPart.replace('GMT', 'UTC');
            } catch (error) {
                return '';
            }
        },
        syncInstructions() {
            this.instructions = this.$refs.instructionsEditor.innerHTML.trim();
            this.$refs.instructionsInput.value = this.instructions;
        },
        focusInstructions() {
            this.$refs.instructionsEditor.focus();
        },
        formatInstructions(command, value = null) {
            this.focusInstructions();
            document.execCommand(command, false, value);
            this.syncInstructions();
        },
        insertInstructionsLink() {
            const url = window.prompt('Enter a link URL');
            if (!url) return;
            this.formatInstructions('createLink', url);
        },
    };
};
</script>
