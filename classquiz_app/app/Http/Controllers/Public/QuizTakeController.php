<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SubmitAnswersRequest;
use App\Models\Answer;
use App\Models\QuizSession;
use App\Services\GradingService;
use App\Services\SessionResumeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class QuizTakeController extends Controller
{
    public function __construct(
        private GradingService $grading,
        private SessionResumeService $resumeService,
    ) {}

    public function show(QuizSession $session)
    {
        $session->load([
            'assignment.quiz.questions' => fn ($query) => $query->where('is_enabled', true),
            'assignment.quiz.questions.choices',
            'answers',
        ]);

        if (!$session->hasStarted()) {
            $assignment = $session->assignment;
            $questions  = $session->assignment->quiz->questions;

            // Randomize question order if enabled
            if ($assignment->setting('randomize_questions')) {
                $questionIds = $questions->pluck('id')->shuffle()->values()->all();
            } else {
                $questionIds = $questions->pluck('id')->values()->all();
            }

            $session->update([
                'status'         => 'in_progress',
                'started_at'     => now(),
                'question_order' => $questionIds,
            ]);
        }

        $questionIds = $session->question_order ?? $session->assignment->quiz->questions->pluck('id')->all();

        // Build ordered questions
        $questionsById = $session->assignment->quiz->questions->keyBy('id');
        $orderedQuestions = collect($questionIds)->map(fn ($id) => $questionsById[$id] ?? null)->filter();

        // Randomize choices per question if enabled
        $orderedQuestions = $orderedQuestions->map(function ($question) {
            if ($question->setting('randomize_choices') && $question->hasChoices()) {
                $question->setRelation('choices', $question->choices->shuffle());
            }
            return $question;
        });

        $answeredMap = $session->answers->keyBy('question_id');
        $timeRemaining = $this->getTimeRemaining($session);

        return response()
            ->view('quiz.take', compact('session', 'orderedQuestions', 'answeredMap', 'timeRemaining'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    public function autoSave(Request $request, QuizSession $session)
    {
        $key = 'quiz-autosave:' . $session->id;
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json(['ok' => false, 'message' => 'Rate limited.'], 429);
        }
        RateLimiter::hit($key, 60);

        $request->validate([
            'question_id'        => ['required', 'integer', 'exists:questions,id'],
            'selected_choice_ids' => ['nullable', 'array'],
            'selected_choice_ids.*' => ['integer', 'exists:choices,id'],
        ]);

        Answer::updateOrCreate(
            ['session_id' => $session->id, 'question_id' => $request->question_id],
            [
                'selected_choice_ids' => $request->selected_choice_ids,
                'saved_at'            => now(),
            ]
        );

        $session->update(['last_activity_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function submit(SubmitAnswersRequest $request, QuizSession $session)
    {
        DB::transaction(function () use ($request, $session) {
            foreach ($request->answers as $answerData) {
                Answer::updateOrCreate(
                    ['session_id' => $session->id, 'question_id' => $answerData['question_id']],
                    [
                        'selected_choice_ids' => $answerData['selected_choice_ids'] ?? null,
                        'saved_at'            => now(),
                    ]
                );
            }

            $session->update([
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]);
        });

        // Auto-grade for objective questions
        $this->grading->gradeSession($session->fresh());

        $request->session()->forget('quiz.pending.' . $session->assignment_id);
        $request->session()->forget('quiz.ready.' . $session->assignment_id);

        $showScore = $session->assignment->setting('show_score');

        return redirect()->route('quiz.result', $session->id)
            ->with('show_score', $showScore)
            ->withCookie($this->resumeService->clearCookie($session->id));
    }

    private function getTimeRemaining(QuizSession $session): ?int
    {
        $duration = $session->assignment->duration_minutes;
        if (!$duration || !$session->started_at) {
            return null;
        }

        $elapsed = $session->started_at->diffInSeconds(now());
        $remaining = ($duration * 60) - $elapsed;

        return max(0, (int) $remaining);
    }
}
