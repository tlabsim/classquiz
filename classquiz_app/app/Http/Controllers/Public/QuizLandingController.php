<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\AccessCodeMail;
use App\Models\QuizAssignment;
use App\Models\QuizSession;
use App\Services\AccessCodeService;
use App\Services\SessionResumeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class QuizLandingController extends Controller
{
    public function __construct(
        private AccessCodeService $accessCodes,
        private SessionResumeService $resumeService,
    ) {}

    public function show(string $token)
    {
        $assignment = QuizAssignment::findByPublicTokenOrFail($token);
        $assignment->load(['quiz.questions' => fn ($query) => $query->where('is_enabled', true)]);

        if (!$assignment->is_active) {
            abort(404);
        }

        $currentSession = $this->resolveCurrentSession(request(), $assignment);
        $readyToStart = $currentSession
            && request()->session()->get($this->readySessionKey($assignment)) === $currentSession->id;

        return view('quiz.show', compact('assignment', 'currentSession', 'readyToStart'));
    }

    public function register(Request $request, string $token)
    {
        $assignment = QuizAssignment::findByPublicTokenOrFail($token);
        $assignment->load('quiz');

        $data = $request->validate($this->registrationRules($assignment));

        if (!$assignment->access_code_required && !$assignment->isAvailable()) {
            return back()->withErrors(['email' => 'This quiz is not currently available.'])->withInput();
        }

        $this->ensureAttemptAvailable($assignment, $data['email']);
        $this->throttleOrFail('quiz-register:' . $request->ip(), 5, 'email');

        $session = $this->findOrCreatePendingSession(
            $assignment,
            $data['email'],
            $data['name'] ?? null,
            $data['class_id'] ?? null,
            $data['session_id'] ?? null,
        );

        if ($assignment->access_code_required) {
            if (!$assignment->isRegistrationOpen()) {
                return back()->withErrors(['email' => 'Access code pickup is currently closed.'])->withInput();
            }

            if (!$session->access_code || $session->isAccessCodeExpired()) {
                return back()->withErrors([
                    'code' => 'Request a fresh access code first.',
                ])->withInput();
            }

            if (!$this->accessCodes->verify($session, strtoupper((string) $data['code']))) {
                throw ValidationException::withMessages([
                    'code' => 'Invalid or expired access code.',
                ]);
            }
        }

        $this->storePendingSession($request, $assignment, $session);
        $request->session()->put($this->readySessionKey($assignment), $session->id);

        return redirect()->route('quiz.show', $assignment->public_token)
            ->with('success', $assignment->access_code_required
                ? 'Access code verified. You can now start the quiz.'
                : 'Your details are ready. You can now start the quiz.');
    }

    public function requestCode(Request $request, string $token)
    {
        $assignment = QuizAssignment::findByPublicTokenOrFail($token);
        $assignment->load('quiz');

        if (!$assignment->access_code_required) {
            return redirect()->route('quiz.show', $assignment->public_token);
        }

        if (!$assignment->isRegistrationOpen()) {
            return back()->withErrors(['email' => 'Access code pickup is currently closed.'])->withInput();
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => [$assignment->setting('collect_name', false) ? 'nullable' : 'prohibited', 'string', 'max:255'],
            'class_id' => [$assignment->setting('collect_class_id', false) ? 'nullable' : 'prohibited', 'string', 'max:100'],
            'session_id' => ['nullable', 'string'],
        ]);

        $this->ensureAttemptAvailable($assignment, $data['email']);
        $this->throttleOrFail('quiz-register:' . $request->ip(), 5, 'email');

        $session = $this->findOrCreatePendingSession(
            $assignment,
            $data['email'],
            $data['name'] ?? null,
            $data['class_id'] ?? null,
            $data['session_id'] ?? null,
        );

        $plainCode = $this->accessCodes->regenerate($session);

        Mail::to($session->email)->send(new AccessCodeMail(
            $session,
            $plainCode,
            $assignment->displayTitle()
        ));

        $this->storePendingSession($request, $assignment, $session);
        $request->session()->forget($this->readySessionKey($assignment));

        return redirect()->route('quiz.show', $assignment->public_token)
            ->with('success', 'A fresh access code has been sent to your email.');
    }

    public function start(Request $request, string $token)
    {
        $assignment = QuizAssignment::findByPublicTokenOrFail($token);
        $assignment->load('quiz');

        $data = $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        $session = QuizSession::query()
            ->where('id', $data['session_id'])
            ->where('assignment_id', $assignment->id)
            ->firstOrFail();

        if ($session->isSubmitted()) {
            return redirect()->route('quiz.result', $session->id)
                ->with('info', 'This quiz has already been submitted.');
        }

        if ($request->session()->get($this->pendingSessionKey($assignment)) !== $session->id) {
            abort(403, 'You do not have access to this quiz session.');
        }

        if ($assignment->access_code_required
            && $request->session()->get($this->readySessionKey($assignment)) !== $session->id) {
            return redirect()->route('quiz.show', $assignment->public_token)
                ->withErrors(['code' => 'Verify your access code before starting the quiz.']);
        }

        if (!$assignment->access_code_required) {
            $request->session()->put($this->readySessionKey($assignment), $session->id);
        }

        if (!$assignment->isAvailable()) {
            return redirect()->route('quiz.show', $assignment->public_token)
                ->withErrors(['email' => 'This quiz is not currently available.']);
        }

        $session->update(['status' => 'active']);

        $cookie = $this->resumeService->storeCookie($session->id);

        return redirect()->route('quiz.take', $session->id)
            ->withCookie($cookie);
    }

    private function findOrCreatePendingSession(
        QuizAssignment $assignment,
        string $email,
        ?string $name,
        ?string $classId,
        ?string $sessionId = null,
    ): QuizSession {
        $session = null;

        if ($sessionId) {
            $session = QuizSession::query()
                ->where('id', $sessionId)
                ->where('assignment_id', $assignment->id)
                ->whereIn('status', ['pending', 'active'])
                ->first();
        }

        if (!$session) {
            $session = QuizSession::query()
                ->where('assignment_id', $assignment->id)
                ->where('email', $email)
                ->where('status', 'pending')
                ->latest()
                ->first();
        }

        if ($session) {
            $session->update([
                'email' => $email,
                'name' => $assignment->setting('collect_name', false) ? $name : null,
                'class_id' => $assignment->setting('collect_class_id', false) ? $classId : null,
            ]);

            return $session->fresh();
        }

        return QuizSession::create([
            'assignment_id' => $assignment->id,
            'email' => $email,
            'name' => $assignment->setting('collect_name', false) ? $name : null,
            'class_id' => $assignment->setting('collect_class_id', false) ? $classId : null,
            'status' => 'pending',
        ]);
    }

    private function ensureAttemptAvailable(QuizAssignment $assignment, string $email): void
    {
        $maxAttempts = (int) $assignment->setting('max_attempts', 1);
        $attemptCount = QuizSession::query()
            ->where('assignment_id', $assignment->id)
            ->where('email', $email)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($attemptCount >= $maxAttempts) {
            throw ValidationException::withMessages([
                'email' => 'You have already used all allowed attempts for this quiz.',
            ]);
        }
    }

    private function throttleOrFail(string $key, int $maxAttempts, string $field): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                $field => 'Too many attempts. Please try again in a minute.',
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    private function resolveCurrentSession(Request $request, QuizAssignment $assignment): ?QuizSession
    {
        $sessionId = $request->session()->get($this->pendingSessionKey($assignment));

        if (!$sessionId) {
            return null;
        }

        return QuizSession::query()
            ->where('id', $sessionId)
            ->where('assignment_id', $assignment->id)
            ->whereIn('status', ['pending', 'active'])
            ->first();
    }

    private function storePendingSession(Request $request, QuizAssignment $assignment, QuizSession $session): void
    {
        $request->session()->put($this->pendingSessionKey($assignment), $session->id);
    }

    private function pendingSessionKey(QuizAssignment $assignment): string
    {
        return 'quiz.pending.' . $assignment->id;
    }

    private function readySessionKey(QuizAssignment $assignment): string
    {
        return 'quiz.ready.' . $assignment->id;
    }

    private function registrationRules(QuizAssignment $assignment): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => [$assignment->setting('collect_name', false) ? 'nullable' : 'prohibited', 'string', 'max:255'],
            'class_id' => [$assignment->setting('collect_class_id', false) ? 'nullable' : 'prohibited', 'string', 'max:100'],
            'session_id' => ['nullable', 'string'],
            'code' => [$assignment->access_code_required ? 'required' : 'nullable', 'string', 'size:6'],
        ];
    }
}
