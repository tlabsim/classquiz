<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Services\AccessCodeService;
use App\Services\SessionResumeService;
use App\Services\TokenService;
use Illuminate\Http\Request;

class QuizResumeController extends Controller
{
    public function __construct(
        private TokenService $tokens,
        private AccessCodeService $accessCodes,
        private SessionResumeService $resumeService,
    ) {}

    public function resume(Request $request, string $session, string $token)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired resume link.');
        }

        $quizSession = QuizSession::findOrFail($session);

        if ($quizSession->isSubmitted()) {
            return redirect()->route('quiz.result', $quizSession->id)
                ->with('info', 'This quiz has already been submitted.');
        }

        if (!$this->tokens->verifyResumeToken($quizSession, $token)) {
            abort(403, 'Invalid resume token.');
        }

        $cookie = $this->resumeService->storeCookie($quizSession->id);

        return redirect()->route('quiz.take', $quizSession->id)
            ->withCookie($cookie);
    }

    public function result(QuizSession $session)
    {
        $session->load([
            'assignment.quiz.questions' => fn ($query) => $query->where('is_enabled', true),
            'answers.question.choices',
        ]);

        $showScore = $session->assignment->setting('show_score')
            && in_array($session->status, ['submitted', 'graded']);
        $showCorrectAnswers = $showScore && $session->assignment->setting('show_correct_answers', false);
        $showFeedbackAndExplanation = $showCorrectAnswers && $session->assignment->setting('show_feedback_and_explanation', false);

        return view('quiz.result', compact('session', 'showScore', 'showCorrectAnswers', 'showFeedbackAndExplanation'));
    }
}
