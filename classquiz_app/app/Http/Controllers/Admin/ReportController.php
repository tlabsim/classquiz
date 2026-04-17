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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private GradingService $grading) {}

    public function index(Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('view', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);

        $effectiveMaxScore = (float) $assignment->quiz->questions()->where('is_enabled', true)->sum('points');

        $sessions = $assignment->sessions()
            ->with('answers')
            ->orderByDesc('submitted_at')
            ->paginate(30);

        $stats = [
            'total'     => $assignment->sessions()->count(),
            'submitted' => $assignment->sessions()->whereIn('status', ['submitted', 'graded'])->count(),
            'graded'    => $assignment->sessions()->where('status', 'graded')->count(),
            'avg_score' => $assignment->sessions()->where('status', 'graded')->avg('score'),
        ];

        return view('admin.reports.index', compact('assignment', 'sessions', 'stats', 'effectiveMaxScore'));
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

        $effectiveMaxScore = (float) $assignment->quiz->questions()->where('is_enabled', true)->sum('points');

        $session->load('answers.question.choices', 'answers.gradedBy');

        return view('admin.reports.show', compact('assignment', 'session', 'effectiveMaxScore'));
    }

    public function grade(Quiz $quiz, QuizAssignment $assignment, QuizSession $session)
    {
        Gate::authorize('update', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);
        $this->ensureSessionBelongsToAssignment($assignment, $session);

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
}
