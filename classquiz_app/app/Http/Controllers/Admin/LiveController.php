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
        $includeRecentlyGraded = $request->boolean('include_recently_graded');
        $sort = in_array($request->string('sort')->toString(), ['last_active', 'questions', 'score', 'time'], true)
            ? $request->string('sort')->toString()
            : 'last_active';
        $direction = in_array($request->string('direction')->toString(), ['asc', 'desc'], true)
            ? $request->string('direction')->toString()
            : 'desc';

        $baseSessionQuery = QuizSession::query()
            ->whereHas('assignment.quiz', function ($query) use ($user) {
                if (!$user->isAdmin()) {
                    $query->where('creator_id', $user->id);
                }
            });

        $timedOutSessions = (clone $baseSessionQuery)
            ->whereIn('status', ['active', 'in_progress'])
            ->with(['assignment'])
            ->get()
            ->filter(fn (QuizSession $session) => $session->isTimedOut());

        foreach ($timedOutSessions as $timedOutSession) {
            $timedOutSession->update([
                'status' => 'submitted',
                'submitted_at' => $timedOutSession->submitted_at ?? now(),
            ]);

            $this->grading->gradeSession($timedOutSession->fresh());
        }

        $displaySessions = (clone $baseSessionQuery)
            ->where(function ($query) use ($includeRecentlyGraded) {
                $query->whereIn('status', ['active', 'in_progress']);

                if ($includeRecentlyGraded) {
                    $query->orWhere(function ($recentlyGradedQuery) {
                        $recentlyGradedQuery
                            ->where('status', 'graded')
                            ->where('updated_at', '>=', now()->subMinutes(30));
                    });
                }
            })
            ->with([
                'assignment.quiz.questions' => fn ($query) => $query->where('is_enabled', true),
                'assignment.quiz.questions.choices',
                'answers.question.choices',
            ])
            ->withCount('answers')
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
                $liveScore = $session->score !== null ? (float) $session->score : 0.0;

                if ($session->score === null) {
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
                }

                $timeProgress = null;
                $lastActivityTimestamp = $session->last_activity_at?->timestamp
                    ?? $session->started_at?->timestamp
                    ?? $session->updated_at?->timestamp
                    ?? 0;

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
                $session->setAttribute('live_sort_last_active', $lastActivityTimestamp);
                $session->setAttribute('live_sort_questions', $questionProgress);
                $session->setAttribute('live_sort_score', $maxScore > 0 ? round(($liveScore / $maxScore) * 10000) : 0);
                $session->setAttribute('live_sort_time', $session->live_time_elapsed_seconds ?? 0);

                return $session;
            })
            ->sortBy(
                match ($sort) {
                    'questions' => 'live_sort_questions',
                    'score' => 'live_sort_score',
                    'time' => 'live_sort_time',
                    default => 'live_sort_last_active',
                },
                SORT_REGULAR,
                $direction === 'desc',
            )
            ->values();

        $liveAssignments = $displaySessions
            ->groupBy('assignment_id')
            ->map(function ($sessions) {
                $assignment = $sessions->first()->assignment;
                $assignment->setRelation('live_sessions', $sessions->values());

                return $assignment;
            })
            ->values();

        $stats = [
            'live' => $displaySessions->count(),
            'in_progress' => $displaySessions->where('status', 'in_progress')->count(),
            'ready' => $displaySessions->where('status', 'active')->count(),
            'recently_graded' => $displaySessions->where('status', 'graded')->count(),
            'avg_question_progress' => $displaySessions->isNotEmpty()
                ? (int) round($displaySessions->avg('live_question_progress'))
                : 0,
        ];

        return view('admin.live.index', compact(
            'liveAssignments',
            'stats',
            'includeRecentlyGraded',
            'sort',
            'direction',
        ));
    }
}
