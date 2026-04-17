<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizSession;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $quizzesQuery = Quiz::query();
        if (!$user->isAdmin()) {
            $quizzesQuery->where('creator_id', $user->id);
        }

        $stats = [
            'total_quizzes'      => (clone $quizzesQuery)->count(),
            'total_assignments'  => QuizAssignment::whereIn(
                'quiz_id', (clone $quizzesQuery)->pluck('id')
            )->count(),
            'pending_grading'    => QuizSession::whereHas('assignment.quiz', function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('creator_id', $user->id);
                }
            })->where('status', 'submitted')->count(),
        ];

        $recentSessions = QuizSession::with('assignment.quiz')
            ->whereHas('assignment.quiz', function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('creator_id', $user->id);
                }
            })
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentSessions'));
    }
}
