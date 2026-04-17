<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuizRequest;
use App\Models\Quiz;
use App\Services\QuizExportService;
use App\Services\QuizImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Quiz::class);

        $query = Quiz::with(['creator', 'assignments' => fn ($q) => $q->latest()->limit(3)->withCount('sessions')])
            ->when(!$request->user()->isAdmin(), fn ($q) => $q->where('creator_id', $request->user()->id))
            ->withCount(['questions', 'assignments'])
            ->withMax('assignments', 'created_at');

        // Search
        if ($search = $request->input('search')) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'     => $query->oldest(),
            'title_asc'  => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'questions'  => $query->orderByDesc('questions_count'),
            'assignments_recent' => $query
                ->orderByRaw('assignments_max_created_at IS NULL')
                ->orderByDesc('assignments_max_created_at')
                ->latest(),
            'assignments_oldest' => $query
                ->orderByRaw('assignments_max_created_at IS NULL')
                ->orderBy('assignments_max_created_at')
                ->latest(),
            default      => $query->latest(),
        };

        $quizzes = $query->paginate(20)->withQueryString();

        return view('admin.quizzes.index', compact('quizzes', 'search', 'sort'));
    }

    public function create()
    {
        Gate::authorize('create', Quiz::class);
        return view('admin.quizzes.create');
    }

    public function show(Quiz $quiz)
    {
        Gate::authorize('view', $quiz);

        $quiz->load([
            'creator',
            'questions' => fn ($query) => $query->withCount('choices'),
            'assignments' => fn ($query) => $query
                ->withCount('sessions')
                ->latest()
                ->limit(5),
        ]);

        $questions = $quiz->questions;
        $enabledQuestions = $questions->where('is_enabled', true);
        $assignments = $quiz->assignments;
        $activeAssignments = $assignments->where('is_active', true);

        $stats = [
            'question_count' => $questions->count(),
            'enabled_question_count' => $enabledQuestions->count(),
            'question_points' => $questions->sum('points'),
            'enabled_question_points' => $enabledQuestions->sum('points'),
            'assignment_count' => $quiz->assignments()->count(),
            'active_assignment_count' => $activeAssignments->count(),
            'session_count' => $quiz->assignments()->withCount('sessions')->get()->sum('sessions_count'),
        ];

        return view('admin.quizzes.show', compact('quiz', 'assignments', 'stats'));
    }

    public function store(StoreQuizRequest $request)
    {
        $quiz = Quiz::create([
            'creator_id'  => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.quizzes.questions.index', $quiz)
            ->with('success', 'Quiz created. Now add questions.');
    }

    public function edit(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $quiz->loadCount(['questions', 'assignments']);

        return view('admin.quizzes.edit', compact('quiz'));
    }

    public function update(StoreQuizRequest $request, Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $quiz->update($request->only('title', 'description'));

        return back()->with('success', 'Quiz updated.');
    }

    public function destroy(Quiz $quiz)
    {
        Gate::authorize('delete', $quiz);

        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz deleted.');
    }
}
