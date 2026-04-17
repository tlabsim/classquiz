<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Services\GradingService;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    public function __construct(private GradingService $grading) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $liveSessions = QuizSession::query()
            ->with([
                'assignment.quiz.questions' => fn ($query) => $query->where('is_enabled', true),
                'assignment.quiz.questions.choices',
                'answers.question.choices',
            ])
            ->withCount('answers')
            ->whereIn('status', ['active', 'in_progress'])
            ->whereHas('assignment.quiz', function ($query) use ($user) {
                if (!$user->isAdmin()) {
                    $query->where('creator_id', $user->id);
                }
            })
            ->orderByDesc('last_activity_at')
            ->orderByDesc('started_at')
            ->get()
            ->map(function (QuizSession $session) {
                $enabledQuestions = $session->assignment->quiz->questions;
                $totalQuestions = $enabledQuestions->count();
                $answeredCount = min($session->answers_count, $totalQuestions);
                $questionProgress = $totalQuestions > 0
                    ? (int) round(($answeredCount / $totalQuestions) * 100)
                    : 0;
                $maxScore = (float) $enabledQuestions->sum('points');
                $liveScore = 0.0;

                foreach ($enabledQuestions as $question) {
                    $answer = $session->answers->firstWhere('question_id', $question->id);

                    if (!$answer) {
                        continue;
                    }

                    if ($answer->is_manually_graded) {
                        $liveScore += (float) ($answer->points_awarded ?? 0);
                        continue;
                    }

                    if (!$question->hasChoices()) {
                        continue;
                    }

                    [, $points] = $this->grading->gradeQuestion($question, $answer);
                    $liveScore += (float) $points;
                }

                $timeProgress = null;

                if ($session->started_at && $session->assignment->duration_minutes) {
                    $totalSeconds = max(1, $session->assignment->duration_minutes * 60);
                    $elapsedSeconds = min($session->started_at->diffInSeconds(now()), $totalSeconds);
                    $timeProgress = (int) round(($elapsedSeconds / $totalSeconds) * 100);
                    $session->setAttribute('live_time_elapsed_seconds', $elapsedSeconds);
                }

                $session->setAttribute('live_total_questions', $totalQuestions);
                $session->setAttribute('live_answered_count', $answeredCount);
                $session->setAttribute('live_question_progress', $questionProgress);
                $session->setAttribute('live_time_progress', $timeProgress);
                $session->setAttribute('live_score', round($liveScore, 2));
                $session->setAttribute('live_max_score', round($maxScore, 2));

                return $session;
            });

        $liveAssignments = $liveSessions
            ->groupBy('assignment_id')
            ->map(function ($sessions) {
                $assignment = $sessions->first()->assignment;
                $assignment->setRelation('live_sessions', $sessions->values());

                return $assignment;
            })
            ->values();

        $stats = [
            'live' => $liveSessions->count(),
            'in_progress' => $liveSessions->where('status', 'in_progress')->count(),
            'ready' => $liveSessions->where('status', 'active')->count(),
            'avg_question_progress' => $liveSessions->isNotEmpty()
                ? (int) round($liveSessions->avg('live_question_progress'))
                : 0,
        ];

        return view('admin.live.index', compact('liveAssignments', 'stats'));
    }
}
