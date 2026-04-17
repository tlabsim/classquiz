<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssignmentRequest;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    public function index(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $assignments = $quiz->assignments()->latest()->get();

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
            'public_token'       => Str::random(32),
            'is_active'          => $request->boolean('is_active', true),
            'title'              => $request->title,
            'instructions'       => $request->instructions,
            'registration_start' => $request->registration_start,
            'registration_end'   => $request->registration_end,
            'availability_start' => $request->availability_start,
            'availability_end'   => $request->availability_end,
            'duration_minutes'   => $request->duration_minutes,
            'settings'           => $request->settings,
        ]);

        return redirect()->route('admin.quizzes.assignments.show', [$quiz, $assignment])
            ->with('success', 'Assignment created.');
    }

    public function show(Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('view', $assignment);

        $publicUrl = route('quiz.show', $assignment->public_token);

        return view('admin.assignments.show', compact('quiz', 'assignment', 'publicUrl'));
    }

    public function edit(Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('update', $assignment);
        return view('admin.assignments.edit', compact('quiz', 'assignment'));
    }

    public function update(StoreAssignmentRequest $request, Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('update', $assignment);

        $assignment->update([
            'is_active'          => $request->boolean('is_active', $assignment->is_active),
            'title'              => $request->title,
            'instructions'       => $request->instructions,
            'registration_start' => $request->registration_start,
            'registration_end'   => $request->registration_end,
            'availability_start' => $request->availability_start,
            'availability_end'   => $request->availability_end,
            'duration_minutes'   => $request->duration_minutes,
            'settings'           => $request->settings ?? $assignment->settings,
        ]);

        return back()->with('success', 'Assignment updated.');
    }

    public function destroy(Quiz $quiz, QuizAssignment $assignment)
    {
        Gate::authorize('delete', $assignment);

        $assignment->delete();

        return redirect()->route('admin.quizzes.assignments.index', $quiz)
            ->with('success', 'Assignment deleted.');
    }
}
