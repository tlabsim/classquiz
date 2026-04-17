<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizSession;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private GradingService $grading) {}

    public function index(Request $request, Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('view', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);

        $assignment->load(['quiz.questions' => fn ($query) => $query->where('is_enabled', true)]);

        $effectiveMaxScore = (float) $assignment->quiz->questions->sum('points');
        $totalQuestions = $assignment->quiz->questions->count();
        $sort = in_array($request->string('sort')->toString(), ['last_active', 'questions', 'score', 'time', 'submitted'], true)
            ? $request->string('sort')->toString()
            : 'last_active';
        $direction = in_array($request->string('direction')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction')->toString()
            : 'desc';

        $sessions = $assignment->sessions()
            ->with('answers')
            ->withCount('answers')
            ->get()
            ->map(function (QuizSession $session) use ($effectiveMaxScore, $totalQuestions) {
                $answeredCount = min($session->answers_count, $totalQuestions);
                $questionProgress = $totalQuestions > 0
                    ? (int) round(($answeredCount / $totalQuestions) * 100)
                    : 0;
                $lastActivityTimestamp = $session->last_activity_at?->timestamp
                    ?? $session->submitted_at?->timestamp
                    ?? $session->started_at?->timestamp
                    ?? $session->updated_at?->timestamp
                    ?? $session->created_at?->timestamp
                    ?? 0;
                $submittedTimestamp = $session->submitted_at?->timestamp ?? 0;

                $timeSeconds = null;
                if ($session->started_at) {
                    $endedAt = $session->submitted_at
                        ?? $session->last_activity_at
                        ?? $session->updated_at
                        ?? now();

                    $timeSeconds = max(0, $session->started_at->diffInSeconds($endedAt));
                }

                $score = $session->score !== null ? (float) $session->score : 0.0;
                $scoreSort = $effectiveMaxScore > 0
                    ? (int) round(($score / $effectiveMaxScore) * 10000)
                    : 0;

                $session->setAttribute('report_answered_count', $answeredCount);
                $session->setAttribute('report_total_questions', $totalQuestions);
                $session->setAttribute('report_question_progress', $questionProgress);
                $session->setAttribute('report_time_seconds', $timeSeconds);
                $session->setAttribute('report_sort_last_active', $lastActivityTimestamp);
                $session->setAttribute('report_sort_submitted', $submittedTimestamp);
                $session->setAttribute('report_sort_questions', $questionProgress);
                $session->setAttribute('report_sort_score', $scoreSort);
                $session->setAttribute('report_sort_time', $timeSeconds ?? -1);

                return $session;
            })
            ->sortBy(
                match ($sort) {
                    'questions' => 'report_sort_questions',
                    'score' => 'report_sort_score',
                    'time' => 'report_sort_time',
                    'submitted' => 'report_sort_submitted',
                    default => 'report_sort_last_active',
                },
                SORT_REGULAR,
                $direction === 'desc',
            )
            ->values();

        $sessions = $this->paginateCollection($sessions, 60, $request);

        $stats = [
            'total'     => $assignment->sessions()->count(),
            'submitted' => $assignment->sessions()->whereIn('status', ['submitted', 'graded'])->count(),
            'graded'    => $assignment->sessions()->where('status', 'graded')->count(),
            'avg_score' => $assignment->sessions()->where('status', 'graded')->avg('score'),
        ];

        return view('admin.reports.index', compact(
            'assignment',
            'sessions',
            'stats',
            'effectiveMaxScore',
            'sort',
            'direction',
        ));
    }

    public function export(Quiz $quiz, QuizAssignment $assignment): StreamedResponse
    {
        Gate::authorize('view', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);

        $effectiveMaxScore = (float) $assignment->quiz->questions()->where('is_enabled', true)->sum('points');

        $sessions = $assignment->sessions()
            ->orderByDesc('submitted_at')
            ->get();

        $filename = sprintf(
            'report-%s-%s.csv',
            $assignment->id,
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($sessions) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Session ID',
                'Email',
                'Name',
                'Class ID',
                'Status',
                'Score',
                'Max Score',
                'Started At',
                'Submitted At',
                'Last Activity At',
                'Created At',
            ]);

            foreach ($sessions as $session) {
                fputcsv($handle, [
                    $session->id,
                    $session->email,
                    $session->name,
                    $session->class_id,
                    $session->status,
                    $session->score,
                    $effectiveMaxScore,
                    optional($session->started_at)?->toDateTimeString(),
                    optional($session->submitted_at)?->toDateTimeString(),
                    optional($session->last_activity_at)?->toDateTimeString(),
                    optional($session->created_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Quiz $quiz, QuizAssignment $assignment, QuizSession $session)
    {
        Gate::authorize('view', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);
        $this->ensureSessionBelongsToAssignment($assignment, $session);

        $assignment->load(['quiz.questions' => fn ($query) => $query->where('is_enabled', true)]);
        $effectiveMaxScore = (float) $assignment->quiz->questions->sum('points');

        $session->load('answers.question.choices', 'answers.gradedBy');

        $enabledQuestionIds = $assignment->quiz->questions->pluck('id');
        $relevantAnswers = $session->answers->whereIn('question_id', $enabledQuestionIds);
        $answeredCount = $relevantAnswers->filter(fn ($answer) => !empty($answer->selected_choice_ids))->count();
        $correctCount = $relevantAnswers->where('is_correct', true)->count();
        $incorrectCount = max(0, $answeredCount - $correctCount);
        $totalQuestions = $assignment->quiz->questions->count();
        $scorePercent = $effectiveMaxScore > 0 && $session->score !== null
            ? (int) round(((float) $session->score / $effectiveMaxScore) * 100)
            : 0;
        $questionProgress = $totalQuestions > 0
            ? (int) round(($answeredCount / $totalQuestions) * 100)
            : 0;

        $timeSpentSeconds = null;
        if ($session->started_at) {
            $endedAt = $session->submitted_at
                ?? $session->last_activity_at
                ?? $session->updated_at
                ?? now();

            $timeSpentSeconds = max(0, $session->started_at->diffInSeconds($endedAt));
        }

        $summary = compact(
            'answeredCount',
            'correctCount',
            'incorrectCount',
            'totalQuestions',
            'scorePercent',
            'questionProgress',
            'timeSpentSeconds',
        );

        $snapshotQuestions = collect($session->quiz_snapshot['questions'] ?? [])->keyBy('id');
        $hasLegacyChoiceMismatch = $session->hasLegacyChoiceMismatch();

        return view('admin.reports.show', compact(
            'assignment',
            'session',
            'effectiveMaxScore',
            'summary',
            'snapshotQuestions',
            'hasLegacyChoiceMismatch',
        ));
    }

    public function grade(Quiz $quiz, QuizAssignment $assignment, QuizSession $session)
    {
        Gate::authorize('update', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);
        $this->ensureSessionBelongsToAssignment($assignment, $session);

        if ($session->hasLegacyChoiceMismatch()) {
            return back()->withErrors([
                'regrade' => 'This session cannot be safely re-graded from the current quiz because the answer choice references no longer match. The existing stored score was preserved.',
            ]);
        }

        $this->grading->gradeSession($session);

        return back()->with('success', 'Session graded successfully.');
    }

    public function override(Request $request, Quiz $quiz, QuizAssignment $assignment, QuizSession $session, Answer $answer)
    {
        Gate::authorize('update', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);
        $this->ensureSessionBelongsToAssignment($assignment, $session);
        abort_unless($answer->session_id === $session->id, 404);

        $request->validate([
            'points_awarded' => ['required', 'numeric', 'min:0'],
        ]);

        $this->grading->manualOverride($answer, (float) $request->points_awarded, $request->user());

        return back()->with('success', 'Score overridden.');
    }

    private function ensureAssignmentBelongsToQuiz(Quiz $quiz, QuizAssignment $assignment): void
    {
        abort_unless($assignment->quiz_id === $quiz->id, 404);
    }

    private function ensureSessionBelongsToAssignment(QuizAssignment $assignment, QuizSession $session): void
    {
        abort_unless($session->assignment_id === $assignment->id, 404);
    }

    private function paginateCollection($items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->integer('page', 1));
        $total = $items->count();
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => $request->query(),
            ],
        );
    }
}
