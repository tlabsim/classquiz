<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\QuizAssignment;
use App\Models\QuizSession;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(private GradingService $grading) {}

    public function index(QuizAssignment $assignment)
    {
        Gate::authorize('view', $assignment);

        $sessions = $assignment->sessions()
            ->with('answers')
            ->orderByDesc('submitted_at')
            ->paginate(30);

        $stats = [
            'total'     => $assignment->sessions()->count(),
            'submitted' => $assignment->sessions()->whereIn('status', ['submitted', 'graded'])->count(),
            'graded'    => $assignment->sessions()->where('status', 'graded')->count(),
            'avg_score' => $assignment->sessions()->where('status', 'graded')->avg('score'),
        ];

        return view('admin.reports.index', compact('assignment', 'sessions', 'stats'));
    }

    public function show(QuizAssignment $assignment, QuizSession $session)
    {
        Gate::authorize('view', $assignment);

        $session->load('answers.question.choices', 'answers.gradedBy');

        return view('admin.reports.show', compact('assignment', 'session'));
    }

    public function grade(QuizAssignment $assignment, QuizSession $session)
    {
        Gate::authorize('update', $assignment);

        $this->grading->gradeSession($session);

        return back()->with('success', 'Session graded successfully.');
    }

    public function override(Request $request, QuizAssignment $assignment, QuizSession $session, Answer $answer)
    {
        Gate::authorize('update', $assignment);

        $request->validate([
            'points_awarded' => ['required', 'numeric', 'min:0'],
        ]);

        $this->grading->manualOverride($answer, (float) $request->points_awarded, $request->user());

        return back()->with('success', 'Score overridden.');
    }
}
