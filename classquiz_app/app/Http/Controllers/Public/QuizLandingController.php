<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\RegisterForQuizRequest;
use App\Mail\AccessCodeMail;
use App\Models\QuizAssignment;
use App\Models\QuizSession;
use App\Services\AccessCodeService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class QuizLandingController extends Controller
{
    public function __construct(private AccessCodeService $accessCodes) {}

    public function show(string $token)
    {
        $assignment = QuizAssignment::where('public_token', $token)
            ->with('quiz')
            ->firstOrFail();

        if (!$assignment->is_active) {
            abort(404);
        }

        return view('quiz.show', compact('assignment'));
    }

    public function register(RegisterForQuizRequest $request, string $token)
    {
        $assignment = QuizAssignment::where('public_token', $token)
            ->with('quiz')
            ->firstOrFail();

        if (!$assignment->isRegistrationOpen()) {
            return back()->withErrors(['email' => 'Registration is currently closed.']);
        }

        $key = 'quiz-register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many registration attempts. Please try again in a minute.',
            ]);
        }
        RateLimiter::hit($key, 60);

        // Create or find the session
        $session = QuizSession::create([
            'assignment_id'          => $assignment->id,
            'email'                  => $request->email,
            'name'                   => $request->name,
            'class_id'               => $request->class_id,
            'access_code'            => 'pending',
            'access_code_expires_at' => now(),
            'status'                 => 'pending',
        ]);

        $plainCode = $this->accessCodes->regenerate($session);

        Mail::to($session->email)->send(new AccessCodeMail(
            $session,
            $plainCode,
            $assignment->displayTitle()
        ));

        return redirect()->route('quiz.verify', $token)
            ->with('session_id', $session->id)
            ->with('success', 'Check your email for the access code.');
    }
}
