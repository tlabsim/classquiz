<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function index(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $questions = $quiz->questions()->with('choices')->get();
        $copyTargets = Quiz::query()
            ->where('creator_id', $quiz->creator_id)
            ->whereKeyNot($quiz->id)
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.questions.index', compact('quiz', 'questions', 'copyTargets'));
    }

    public function create(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $tags = $this->tagOptions($quiz);

        return view('admin.questions.create', compact('quiz', 'tags'));
    }

    public function store(StoreQuestionRequest $request, Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        $question = null;

        DB::transaction(function () use ($request, $quiz, &$question) {
            $question = $quiz->questions()->create([
                'tag' => $request->tag ?: null,
                'type'       => $request->type,
                'text'       => $request->text,
                'points'     => $request->points,
                'sort_order' => $request->sort_order ?? $quiz->questions()->count(),
                'settings'   => $request->settings,
                'feedback_correct' => $request->feedback_correct,
                'feedback_incorrect' => $request->feedback_incorrect,
                'explanation' => $request->explanation,
            ]);

            if ($request->type === 'tf') {
                $correct = $request->tf_correct; // 'true' or 'false'
                $question->choices()->createMany([
                    ['text' => 'True',  'is_correct' => $correct === 'true',  'sort_order' => 0],
                    ['text' => 'False', 'is_correct' => $correct === 'false', 'sort_order' => 1],
                ]);
            } else {
                foreach ($request->choices ?? [] as $index => $choice) {
                    $question->choices()->create([
                        'text'       => $choice['text'],
                        'is_correct' => (bool) ($choice['is_correct'] ?? false),
                        'sort_order' => $choice['sort_order'] ?? $index,
                    ]);
                }
            }
        });

        if ($request->boolean('save_and_add_another')) {
            return redirect()->route('admin.quizzes.questions.create', $quiz)
                ->with('success', 'Question added. You can add another now.');
        }

        return redirect()->route('admin.quizzes.questions.index', $quiz)
            ->with('success', 'Question added.');
    }

    public function edit(Quiz $quiz, Question $question)
    {
        Gate::authorize('update', $quiz);

        $question->load('choices');

        $tags = $this->tagOptions($quiz);

        return view('admin.questions.edit', compact('quiz', 'question', 'tags'));
    }

    public function update(StoreQuestionRequest $request, Quiz $quiz, Question $question)
    {
        Gate::authorize('update', $quiz);

        DB::transaction(function () use ($request, $question) {
            $question->update([
                'tag' => $request->tag ?: null,
                'type'       => $request->type,
                'text'       => $request->text,
                'points'     => $request->points,
                'sort_order' => $request->sort_order ?? $question->sort_order,
                'settings'   => $request->settings,
                'feedback_correct' => $request->feedback_correct,
                'feedback_incorrect' => $request->feedback_incorrect,
                'explanation' => $request->explanation,
            ]);

            // Replace choices
            $question->choices()->delete();

            if ($request->type === 'tf') {
                $correct = $request->tf_correct; // 'true' or 'false'
                $question->choices()->createMany([
                    ['text' => 'True',  'is_correct' => $correct === 'true',  'sort_order' => 0],
                    ['text' => 'False', 'is_correct' => $correct === 'false', 'sort_order' => 1],
                ]);
            } else {
                foreach ($request->choices ?? [] as $index => $choice) {
                    $question->choices()->create([
                        'text'       => $choice['text'],
                        'is_correct' => (bool) ($choice['is_correct'] ?? false),
                        'sort_order' => $choice['sort_order'] ?? $index,
                    ]);
                }
            }
        });

        return redirect()->route('admin.quizzes.questions.index', $quiz)
            ->with('success', 'Question updated.');
    }

    public function destroy(Quiz $quiz, Question $question)
    {
        Gate::authorize('update', $quiz);

        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    public function toggle(Quiz $quiz, Question $question)
    {
        Gate::authorize('update', $quiz);

        $question->update(['is_enabled' => !$question->is_enabled]);

        return back()->with('success', $question->is_enabled ? 'Question enabled.' : 'Question disabled.');
    }

    public function copy(Quiz $quiz, Question $question)
    {
        Gate::authorize('update', $quiz);

        abort_unless($question->quiz_id === $quiz->id, 404);

        $validated = request()->validate([
            'target_quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
        ]);

        $targetQuiz = Quiz::query()
            ->where('creator_id', $quiz->creator_id)
            ->findOrFail($validated['target_quiz_id']);

        abort_if($targetQuiz->id === $quiz->id, 422, 'Choose a different quiz.');

        $question->loadMissing('choices');

        DB::transaction(function () use ($question, $targetQuiz) {
            $newQuestion = $targetQuiz->questions()->create([
                'tag' => $question->tag,
                'type' => $question->type,
                'text' => $question->text,
                'points' => $question->points,
                'sort_order' => $targetQuiz->questions()->count(),
                'is_enabled' => $question->is_enabled,
                'settings' => $question->settings,
                'feedback_correct' => $question->feedback_correct,
                'feedback_incorrect' => $question->feedback_incorrect,
                'explanation' => $question->explanation,
            ]);

            $newQuestion->choices()->createMany(
                $question->choices->map(fn ($choice) => [
                    'text' => $choice->text,
                    'is_correct' => (bool) $choice->is_correct,
                    'sort_order' => $choice->sort_order,
                ])->all()
            );
        });

        return back()->with('success', 'Question copied to "' . $targetQuiz->title . '".');
    }

    public function reorder(Quiz $quiz)
    {
        Gate::authorize('update', $quiz);

        request()->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach (request('order') as $position => $questionId) {
            $quiz->questions()->where('id', $questionId)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }
    private function tagOptions(Quiz $quiz)
    {
        return Question::query()
            ->whereHas('quiz', fn ($query) => $query->where('creator_id', $quiz->creator_id))
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->distinct()
            ->orderBy('tag')
            ->pluck('tag');
    }
}
