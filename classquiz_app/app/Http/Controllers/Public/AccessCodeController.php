<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\AccessCodeMail;
use App\Models\QuizAssignment;
use App\Models\QuizSession;
use App\Services\AccessCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AccessCodeController extends Controller
{
    public function __construct(private AccessCodeService $accessCodes) {}

    public function showForm(string $token)
    {
        $assignment = QuizAssignment::findByPublicTokenOrFail($token);
        return redirect()->route('quiz.show', $assignment->public_token);
    }

    public function verify(Request $request, string $token)
    {
        $assignment = QuizAssignment::findByPublicTokenOrFail($token);

        if (!$assignment->access_code_required) {
            return redirect()->route('quiz.show', $assignment->public_token);
        }

        $data = $request->validate([
            'session_id' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $key = 'quiz-verify:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please try again in a minute.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $session = QuizSession::query()
            ->where('id', $data['session_id'])
            ->where('assignment_id', $assignment->id)
            ->firstOrFail();

        if (!$this->accessCodes->verify($session, strtoupper($data['code']))) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired access code.',
            ]);
        }

        $request->session()->put('quiz.pending.' . $assignment->id, $session->id);
        $request->session()->put('quiz.ready.' . $assignment->id, $session->id);

        return redirect()->route('quiz.show', $assignment->public_token)
            ->with('success', 'Access code verified. You can now start the quiz.');
    }

    public function resend(Request $request, string $token)
    {
        $assignment = QuizAssignment::findByPublicTokenOrFail($token);

        if (!$assignment->access_code_required) {
            return redirect()->route('quiz.show', $assignment->public_token);
        }

        $data = $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        if (!$assignment->isRegistrationOpen()) {
            return redirect()->route('quiz.show', $assignment->public_token)
                ->withErrors(['email' => 'Access code pickup is currently closed.']);
        }

        $key = 'quiz-register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'session_id' => 'Too many attempts. Please try again in a minute.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $session = QuizSession::query()
            ->where('id', $data['session_id'])
            ->where('assignment_id', $assignment->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $plainCode = $this->accessCodes->regenerate($session);

        Mail::to($session->email)->send(new AccessCodeMail(
            $session,
            $plainCode,
            $assignment->displayTitle()
        ));

        $request->session()->put('quiz.pending.' . $assignment->id, $session->id);
        $request->session()->forget('quiz.ready.' . $assignment->id);

        return redirect()->route('quiz.show', $assignment->public_token)
            ->with('success', 'A fresh access code has been sent to your email.');
    }
}
