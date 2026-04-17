<?php

namespace App\Services;

use App\Models\Quiz;
use Illuminate\Support\Collection;

class QuizExportService
{
    public function export(Quiz $quiz): array
    {
        $quiz->load('questions.choices');

        return [
            'version'     => '1.0',
            'title'       => $quiz->title,
            'description' => $quiz->description,
            'questions'   => $quiz->questions->map(function ($question) {
                return [
                    'tag'        => $question->tag,
                    'type'       => $question->type,
                    'text'       => $question->text,
                    'points'     => (float) $question->points,
                    'sort_order' => $question->sort_order,
                    'settings'   => $question->settings ?? [],
                    'feedback_correct' => $question->feedback_correct,
                    'feedback_incorrect' => $question->feedback_incorrect,
                    'explanation' => $question->explanation,
                    'choices'    => $question->choices->map(fn ($c) => [
                        'text'       => $c->text,
                        'is_correct' => $c->is_correct,
                        'sort_order' => $c->sort_order,
                    ])->all(),
                ];
            })->all(),
        ];
    }
}
