<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssignmentRequest;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function index(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $assignments = $quiz->assignments()
            ->with([
                'sessions' => fn ($query) => $query->latest()->limit(5),
            ])
            ->withCount('sessions')
            ->latest()
            ->get();

        return view('admin.assignments.index', compact('quiz', 'assignments'));
    }

    public function create(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);
        return view('admin.assignments.create', compact('quiz'));
    }

    public function store(StoreAssignmentRequest $request, Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $assignment = $quiz->assignments()->create([
            'public_token'       => QuizAssignment::generatePublicToken(),
            'is_active'          => $request->boolean('is_active', true),
            'title'              => $request->title,
            'instructions'       => $request->instructions,
            'availability_start' => $request->availability_start,
            'availability_end'   => $request->availability_end,
            'access_code_required' => $request->boolean('access_code_required', true),
            'access_code_starts_before_minutes' => $request->integer('access_code_starts_before_minutes', 15),
            'duration_minutes'   => $request->duration_minutes,
            'settings'           => $request->settings,
        ]);

        return redirect()->route('admin.quizzes.assignments.show', [$quiz, $assignment])
            ->with('success', 'Assignment created.');
    }

    public function show(Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('view', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);

        $assignment->load([
            'quiz.questions',
            'sessions' => fn ($query) => $query->latest()->limit(8),
        ])->loadCount('sessions');

        $sessionStats = [
            'total' => $assignment->sessions()->count(),
            'in_progress' => $assignment->sessions()->where('status', 'in_progress')->count(),
            'submitted' => $assignment->sessions()->whereIn('status', ['submitted', 'graded'])->count(),
            'graded' => $assignment->sessions()->where('status', 'graded')->count(),
        ];

        $publicUrl = route('quiz.show', $assignment->public_token);

        return view('admin.assignments.show', compact('quiz', 'assignment', 'publicUrl', 'sessionStats'));
    }

    public function edit(Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('update', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);
        $quiz->loadCount('questions');
        return view('admin.assignments.edit', compact('quiz', 'assignment'));
    }

    public function update(StoreAssignmentRequest $request, Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('update', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);

        $assignment->update([
            'is_active'          => $request->boolean('is_active', $assignment->is_active),
            'title'              => $request->title,
            'instructions'       => $request->instructions,
            'availability_start' => $request->availability_start,
            'availability_end'   => $request->availability_end,
            'access_code_required' => $request->boolean('access_code_required', $assignment->access_code_required),
            'access_code_starts_before_minutes' => $request->integer('access_code_starts_before_minutes', $assignment->access_code_starts_before_minutes),
            'duration_minutes'   => $request->duration_minutes,
            'settings'           => $request->settings ?? $assignment->settings,
        ]);

        return back()->with('success', 'Assignment updated.');
    }

    public function destroy(Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('delete', $assignment);
        $this->ensureAssignmentBelongsToQuiz($quiz, $assignment);

        $assignment->delete();

        return redirect()->route('admin.quizzes.assignments.index', $quiz)
            ->with('success', 'Assignment deleted.');
    }

    private function ensureAssignmentBelongsToQuiz(Quiz $quiz, QuizAssignment $assignment): void
    {
        abort_unless($assignment->quiz_id === $quiz->id, 404);
    }
}
