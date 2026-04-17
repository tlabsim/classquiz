<?php

namespace App\Http\Middleware;

use App\Models\QuizSession;
use App\Services\SessionResumeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionOwnership
{
    public function __construct(private SessionResumeService $resumeService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->route('session');

        if (!$session instanceof QuizSession) {
            abort(404);
        }

        if (!$this->resumeService->hasOwnership($request, $session)) {
            abort(403, 'You do not have access to this quiz session.');
        }

        return $next($request);
    }
}
