<?php

namespace App\Http\Middleware;

use App\Models\QuizSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->route('session');

        if (!$session instanceof QuizSession) {
            abort(404);
        }

        if ($session->isSubmitted()) {
            return redirect()->route('quiz.result', ['session' => $session->id])
                ->with('info', 'This quiz has already been submitted.');
        }

        if ($session->isTimedOut()) {
            // Auto-submit on timeout
            $session->update([
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]);

            return redirect()->route('quiz.result', ['session' => $session->id])
                ->with('warning', 'Time is up. Your quiz has been automatically submitted.');
        }

        return $next($request);
    }
}
