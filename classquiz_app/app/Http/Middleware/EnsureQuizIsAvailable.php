<?php

namespace App\Http\Middleware;

use App\Models\QuizAssignment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureQuizIsAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        $assignment = $request->route('assignment');

        if (!$assignment instanceof QuizAssignment) {
            $assignment = QuizAssignment::where(
                'public_token', $request->route('token')
            )->firstOrFail();
        }

        if (!$assignment->isAvailable()) {
            abort(403, 'This quiz is not currently available.');
        }

        return $next($request);
    }
}
