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
            ->withCount('questions');

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
