<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QuizAssignment;
use App\Models\QuizSession;
use App\Services\AccessCodeService;
use App\Services\SessionResumeService;
use App\Services\TokenService;
use App\Mail\ResumeQuizMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AccessCodeController extends Controller
{
    public function __construct(
        private AccessCodeService $accessCodes,
        private TokenService $tokens,
        private SessionResumeService $resumeService,
    ) {}

    public function showForm(string $token)
    {
        $assignment = QuizAssignment::where('public_token', $token)
            ->firstOrFail();

        return view('quiz.verify', compact('assignment'));
    }

    public function verify(Request $request, string $token)
    {
        $assignment = QuizAssignment::where('public_token', $token)
            ->with('quiz')
            ->firstOrFail();

        $request->validate([
            'session_id' => ['required', 'string'],
            'code'       => ['required', 'string', 'size:6'],
        ]);

        $key = 'quiz-verify:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please try again in a minute.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $session = QuizSession::where('id', $request->session_id)
            ->where('assignment_id', $assignment->id)
            ->firstOrFail();

        if (!$this->accessCodes->verify($session, strtoupper($request->code))) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired access code.',
            ]);
        }

        RateLimiter::clear($key);

        $session->update(['status' => 'active']);

        // Send resume link if enabled
        if ($assignment->setting('allow_resume')) {
            $plainToken = $this->tokens->generateResumeToken($session);
            $resumeUrl  = $this->tokens->buildResumeUrl($session, $plainToken);

            Mail::to($session->email)->send(new ResumeQuizMail(
                $session,
                $resumeUrl,
                $assignment->displayTitle()
            ));
        }

        $cookie = $this->resumeService->storeCookie($session->id);

        return redirect()->route('quiz.take', $session->id)
            ->withCookie($cookie);
    }

    public function resend(Request $request, string $token)
    {
        $assignment = QuizAssignment::where('public_token', $token)
            ->with('quiz')
            ->firstOrFail();

        $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        $session = QuizSession::where('id', $request->session_id)
            ->where('assignment_id', $assignment->id)
            ->firstOrFail();

        if ($session->isSubmitted()) {
            return back()->withErrors(['session_id' => 'This session has already been submitted.']);
        }

        $key = 'quiz-register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'session_id' => 'Too many attempts. Please try again in a minute.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $plainCode = $this->accessCodes->regenerate($session);

        Mail::to($session->email)->send(new \App\Mail\AccessCodeMail(
            $session,
            $plainCode,
            $assignment->displayTitle()
        ));

        return back()->with('success', 'A new access code has been sent.');
    }
}
