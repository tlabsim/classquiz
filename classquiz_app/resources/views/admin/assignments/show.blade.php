@extends('layouts.admin')

@section('title', $assignment->displayTitle())

@section('breadcrumb')
    <a href="{{ route('admin.quizzes.index') }}" class="hover:text-gray-700 transition-colors">Quizzes</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="hover:text-gray-700 transition-colors truncate max-w-40">{{ $quiz->title }}</a>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.quizzes.assignments.index', $quiz) }}" class="hover:text-gray-700 transition-colors">Assignments</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-700 truncate max-w-40">{{ $assignment->displayTitle() }}</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="{{ $assignment->is_active ? 'cq-badge-green' : 'inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700' }}">
                    {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                </span>
                @if($assignment->duration_minutes)
                    <span class="cq-badge-gray">{{ $assignment->duration_minutes }} min</span>
                @endif
                <span class="cq-badge-blue">{{ $assignment->public_token }}</span>
            </div>
            <h1 class="cq-page-title">{{ $assignment->displayTitle() }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $quiz->title }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.quizzes.assignments.edit', [$quiz, $assignment]) }}" class="cq-btn-secondary cq-btn-sm">Edit assignment</a>
            <a href="{{ route('admin.quizzes.assignments.report', [$quiz, $assignment]) }}" class="cq-btn-primary cq-btn-sm">View reports</a>
        </div>
    </div>

    @unless($assignment->is_active)
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
            <p class="text-sm font-semibold text-red-800">This assignment is currently inactive.</p>
            <p class="mt-1 text-sm text-red-700">Students cannot access it until you mark the assignment active, even if the availability window is open.</p>
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="cq-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sessions</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $sessionStats['total'] }}</p>
        </div>
        <div class="cq-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">In progress</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $sessionStats['in_progress'] }}</p>
        </div>
        <div class="cq-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Submitted</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $sessionStats['submitted'] }}</p>
        </div>
        <div class="cq-card px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Graded</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $sessionStats['graded'] }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
        <div class="space-y-6">
            <div class="cq-card overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="cq-section-title">Student Access</h2>
                </div>
                <div class="space-y-5 px-6 py-5">
                    <div class="grid gap-4 sm:grid-cols-[minmax(0,13rem)_minmax(0,1fr)]">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Assignment code</p>
                            <p class="mt-2 font-mono text-xl font-semibold tracking-[0.18em] text-gray-900">{{ $assignment->public_token }}</p>
                            <button type="button"
                                    data-copy-text="{{ $assignment->public_token }}"
                                    data-copy-label="Assignment code"
                                    class="mt-3 cq-btn-secondary cq-btn-sm">
                                Copy code
                            </button>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Public link</p>
                            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                                <input type="text"
                                       value="{{ $publicUrl }}"
                                       readonly
                                       class="cq-field flex-1 bg-gray-50 font-mono text-sm">
                                <button type="button"
                                        data-copy-text="{{ $publicUrl }}"
                                        data-copy-label="Public link"
                                        class="cq-btn-secondary">
                                    Copy link
                                </button>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Students can open the direct link or enter the 10-character code on the home page.</p>
                        </div>
                    </div>
                </div>
            </div>

            @if($assignment->instructions)
                <div class="cq-card overflow-hidden">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="cq-section-title">Instructions</h2>
                    </div>
                    <div class="px-6 py-5">
                        <div class="cq-richtext text-sm text-gray-700">{!! $assignment->instructions !!}</div>
                    </div>
                </div>
            @endif

            <div class="cq-card overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="cq-section-title">Latest Sessions</h2>
                </div>
                <div class="px-6 py-5">
                    @if($assignment->sessions->isEmpty())
                        <p class="text-sm text-gray-500">No student sessions yet.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($assignment->sessions as $session)
                                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-100 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900">{{ $session->email }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ optional($session->submitted_at ?? $session->last_activity_at)->diffForHumans() }}</p>
                                    </div>
                                    <span class="cq-badge-gray">{{ ucfirst(str_replace('_', ' ', $session->status)) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="cq-card px-6 py-5">
                <h2 class="cq-section-title">Timing and Delivery</h2>
                <div class="mt-4 space-y-3 text-sm text-gray-600">
                    <div>
                        <p class="font-medium text-gray-800">Timezone</p>
                        <p>{{ $assignment->timezone() }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Status</p>
                        <p>{{ $assignment->is_active ? 'Active and eligible for availability checks' : 'Inactive. Availability window is currently ignored.' }}</p>
                    </div>
                    @if($assignment->is_active)
                        <div>
                            <p class="font-medium text-gray-800">Availability</p>
                            <p>{{ $assignment->displayDateTime($assignment->availability_start) ?? 'Open immediately' }} to {{ $assignment->displayDateTime($assignment->availability_end) ?? 'No close time' }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="font-medium text-gray-800">Duration</p>
                        <p>{{ $assignment->duration_minutes ? $assignment->duration_minutes . ' minutes' : 'No time limit' }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Access</p>
                        <p>
                            @if($assignment->access_code_required)
                                Access code required.
                                Available from {{ $assignment->displayDateTime($assignment->accessCodeOpensAt()) ?? 'immediately' }}.
                            @else
                                Students can start directly when the quiz becomes available.
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="cq-card px-6 py-5">
                <h2 class="cq-section-title">Student Data Collection</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="cq-badge-green">Email collected</span>
                    <span class="{{ $assignment->setting('collect_name', false) ? 'cq-badge-green' : 'cq-badge-gray' }}">Name {{ $assignment->setting('collect_name', false) ? 'on' : 'off' }}</span>
                    <span class="{{ $assignment->setting('collect_class_id', false) ? 'cq-badge-green' : 'cq-badge-gray' }}">Class ID {{ $assignment->setting('collect_class_id', false) ? 'on' : 'off' }}</span>
                </div>
            </div>

            <div class="cq-card px-6 py-5">
                <h2 class="cq-section-title">Student Experience</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="{{ $assignment->setting('allow_resume') ? 'cq-badge-green' : 'cq-badge-gray' }}">Resume {{ $assignment->setting('allow_resume') ? 'on' : 'off' }}</span>
                    <span class="{{ $assignment->setting('randomize_questions') ? 'cq-badge-green' : 'cq-badge-gray' }}">Question shuffle {{ $assignment->setting('randomize_questions') ? 'on' : 'off' }}</span>
                    <span class="cq-badge-blue">{{ $assignment->setting('question_presentation', 'one_per_page') === 'one_per_page' ? 'One question per page' : 'All questions on one page' }}</span>
                    @if($assignment->setting('question_presentation', 'one_per_page') === 'one_per_page')
                        <span class="{{ $assignment->setting('allow_modify_previous_answers', true) ? 'cq-badge-green' : 'cq-badge-gray' }}">Back navigation {{ $assignment->setting('allow_modify_previous_answers', true) ? 'on' : 'off' }}</span>
                    @endif
                    <span class="cq-badge-blue">{{ $assignment->setting('max_attempts', 1) }} {{ \Illuminate\Support\Str::plural('attempt', $assignment->setting('max_attempts', 1)) }}</span>
                </div>
            </div>

            <div class="cq-card px-6 py-5">
                <h2 class="cq-section-title">Results Visibility</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="{{ $assignment->setting('show_score') ? 'cq-badge-green' : 'cq-badge-gray' }}">Score {{ $assignment->setting('show_score') ? 'shown' : 'hidden' }}</span>
                    <span class="{{ $assignment->setting('show_correct_answers', false) ? 'cq-badge-green' : 'cq-badge-gray' }}">Correct answers {{ $assignment->setting('show_correct_answers', false) ? 'shown' : 'hidden' }}</span>
                    <span class="{{ $assignment->setting('show_feedback_and_explanation', false) ? 'cq-badge-green' : 'cq-badge-gray' }}">Feedback and explanation {{ $assignment->setting('show_feedback_and_explanation', false) ? 'shown' : 'hidden' }}</span>
                </div>
            </div>

            <div class="cq-card px-6 py-5">
                <h2 class="cq-section-title">Quiz Coverage</h2>
                <p class="mt-2 text-sm text-gray-500">This assignment currently points to {{ $assignment->quiz->questions->count() }} questions from the quiz.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('admin.quizzes.questions.index', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Manage questions</a>
                    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="cq-btn-secondary cq-btn-sm">Quiz overview</a>
                </div>
            </div>

            <div class="rounded-2xl border border-red-200 bg-red-50 px-6 py-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-red-700">Danger Zone</h2>
                <p class="mt-2 text-sm text-red-700">Delete this assignment permanently. Existing sessions and reports tied to it will also be removed.</p>
                <form method="POST"
                      action="{{ route('admin.quizzes.assignments.destroy', [$quiz, $assignment]) }}"
                      onsubmit="return confirm('Delete this assignment permanently? This cannot be undone.')"
                      class="mt-4">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="cq-btn-danger cq-btn-sm">Delete assignment</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
