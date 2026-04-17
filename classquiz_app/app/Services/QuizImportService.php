<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizImportService
{
    private const SUPPORTED_VERSIONS = ['1.0'];

    public function import(array $data, User $creator): Quiz
    {
        $this->validate($data);

        return DB::transaction(function () use ($data, $creator) {
            $quiz = Quiz::create([
                'creator_id'  => $creator->id,
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            foreach ($data['questions'] as $index => $qData) {
                $question = $quiz->questions()->create([
                    'tag'        => $qData['tag'] ?? null,
                    'type'       => $qData['type'],
                    'text'       => $qData['text'],
                    'points'     => $qData['points'] ?? 1,
                    'sort_order' => $qData['sort_order'] ?? $index,
                    'settings'   => $qData['settings'] ?? null,
                    'feedback_correct' => $qData['feedback_correct'] ?? null,
                    'feedback_incorrect' => $qData['feedback_incorrect'] ?? null,
                    'explanation' => $qData['explanation'] ?? null,
                ]);

                foreach ($qData['choices'] ?? [] as $cIndex => $cData) {
                    $question->choices()->create([
                        'text'       => $cData['text'],
                        'is_correct' => $cData['is_correct'] ?? false,
                        'sort_order' => $cData['sort_order'] ?? $cIndex,
                    ]);
                }
            }

            return $quiz;
        });
    }

    private function validate(array $data): void
    {
        if (!isset($data['version']) || !in_array($data['version'], self::SUPPORTED_VERSIONS)) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported or missing quiz export version.',
            ]);
        }

        if (empty($data['title'])) {
            throw ValidationException::withMessages([
                'file' => 'Quiz title is required.',
            ]);
        }

        if (!isset($data['questions']) || !is_array($data['questions'])) {
            throw ValidationException::withMessages([
                'file' => 'Questions array is required.',
            ]);
        }
    }
}
