<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\TakerResultsRequestMail;
use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class TakerResultsController extends Controller
{
    /**
     * Show the "find my results" form.
     */
    public function show()
    {
        return view('public.results-lookup');
    }

    /**
     * Accept the email, look up completed sessions, and send a results email.
     *
     * We always respond with the same success message regardless of whether the
     * email exists — this prevents email enumeration.
     */
    public function request(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ]);

        $email = strtolower(trim($request->input('email')));

        // Rate-limit: 3 requests per 10 minutes per IP
        $key = 'taker-results:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Too many requests. Please try again in {$seconds} seconds.",
            ])->withInput();
        }
        RateLimiter::hit($key, 600);

        $sessions = QuizSession::with('assignment.quiz')
            ->where('email', $email)
            ->whereIn('status', ['submitted', 'graded'])
            ->latest('submitted_at')
            ->get();

        // Always send a mail (even empty) to avoid enumeration timing attacks,
        // but skip actual delivery when no sessions to reduce spam.
        if ($sessions->isNotEmpty()) {
            Mail::to($email)->send(new TakerResultsRequestMail($sessions));
        }

        return back()->with(
            'status',
            'If we found any completed quizzes for that email, a results summary has been sent.'
        );
    }
}
