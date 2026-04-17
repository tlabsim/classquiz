<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuizExportService;
use App\Services\QuizImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportExportController extends Controller
{
    public function __construct(
        private QuizExportService $exporter,
        private QuizImportService $importer,
    ) {}

    public function export(Quiz $quiz)
    {
        Gate::authorize('view', $quiz);

        $data = $this->exporter->export($quiz);

        $filename = $this->makeExportFilename($quiz->title, 'json');

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportQuestion(Request $request, Quiz $quiz, Question $question)
    {
        Gate::authorize('update', $quiz);

        abort_unless($question->quiz_id === $quiz->id, 404);

        $question->loadMissing('choices');

        $data = [
            'version' => 'classquiz-question-v1',
            'exported_at' => now()->toIso8601String(),
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
            ],
            'question' => [
                'id' => $question->id,
                'type' => $question->type,
                'text' => $question->text,
                'points' => $question->points,
                'tag' => $question->tag,
                'is_enabled' => (bool) $question->is_enabled,
                'settings' => $question->settings,
                'feedback_correct' => $question->feedback_correct,
                'feedback_incorrect' => $question->feedback_incorrect,
                'explanation' => $question->explanation,
                'choices' => $question->choices->map(fn ($choice) => [
                    'text' => $choice->text,
                    'is_correct' => (bool) $choice->is_correct,
                    'sort_order' => $choice->sort_order,
                ])->values()->all(),
            ],
        ];

        if ($request->boolean('download', true)) {
            $filename = $this->makeExportFilename(
                ($quiz->title ?: 'quiz') . '-question-' . $question->id,
                'json'
            );

            return response()->json($data)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        return response()->json($data);
    }

    public function importForm()
    {
        Gate::authorize('create', Quiz::class);
        return view('admin.quizzes.import');
    }

    public function import(Request $request)
    {
        Gate::authorize('create', Quiz::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:json', 'max:2048'],
        ]);

        $contents = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'file' => 'Invalid JSON file.',
            ]);
        }

        $quiz = $this->importer->import($data, $request->user());

        return redirect()->route('admin.quizzes.questions.index', $quiz)
            ->with('success', 'Quiz imported successfully.');
    }

    private function makeExportFilename(string $title, string $extension): string
    {
        $slug = Str::slug($title);

        if ($slug === '') {
            $slug = 'export';
        }

        return $slug . '-' . now()->format('Ymd-His') . '.' . $extension;
    }
}
