<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GradingService
{
    public function gradeSession(QuizSession $session): void
    {
        if (!empty($session->quiz_snapshot['questions'])) {
            $this->gradeSessionFromSnapshot($session);
            return;
        }

        $session->load([
            'assignment.quiz.questions' => fn ($query) => $query->where('is_enabled', true),
            'assignment.quiz.questions.choices',
            'answers.question.choices',
        ]);

        DB::transaction(function () use ($session) {
            $totalScore = 0;
            $maxScore   = 0;
            $answersByQuestion = $session->answers->keyBy('question_id');

            foreach ($session->assignment->quiz->questions as $question) {
                $maxScore += (float) $question->points;
                $answer = $answersByQuestion->get($question->id);

                if (!$answer || $answer->is_manually_graded) {
                    $totalScore += (float) ($answer?->points_awarded ?? 0);
                    continue;
                }

                $gradingQuestion = $answer->question && $answer->question->id === $question->id
                    ? $answer->question
                    : $question;

                if ($gradingQuestion->hasChoices() && $gradingQuestion->choices->isEmpty()) {
                    $totalScore += (float) ($answer->points_awarded ?? 0);
                    continue;
                }

                $selectedIds = collect($answer->selected_choice_ids ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->values();
                $currentChoiceIds = $gradingQuestion->choices
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if (
                    $gradingQuestion->hasChoices()
                    && $selectedIds->isNotEmpty()
                    && $selectedIds->intersect($currentChoiceIds)->isEmpty()
                ) {
                    $totalScore += (float) ($answer->points_awarded ?? 0);
                    continue;
                }

                [$isCorrect, $points] = $this->gradeQuestion($gradingQuestion, $answer);

                $answer->update([
                    'is_correct'     => $isCorrect,
                    'points_awarded' => $points,
                ]);

                $totalScore += $points;
            }

            $session->update([
                'score'     => $totalScore,
                'max_score' => $maxScore,
                'status'    => 'graded',
            ]);
        });
    }

    private function gradeSessionFromSnapshot(QuizSession $session): void
    {
        $session->loadMissing('answers');

        DB::transaction(function () use ($session) {
            $questions = collect($session->quiz_snapshot['questions'] ?? []);
            $answersByQuestionId = $session->answers->keyBy('question_id');
            $totalScore = 0.0;
            $maxScore = 0.0;

            foreach ($questions as $questionSnapshot) {
                $questionId = (int) data_get($questionSnapshot, 'id');
                $maxScore += (float) data_get($questionSnapshot, 'points', 0);

                /** @var \App\Models\Answer|null $answer */
                $answer = $answersByQuestionId->get($questionId);

                if (!$answer || $answer->is_manually_graded) {
                    $totalScore += (float) ($answer?->points_awarded ?? 0);
                    continue;
                }

                [$isCorrect, $points] = $this->gradeSnapshotQuestion($questionSnapshot, $answer);

                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_awarded' => $points,
                ]);

                $totalScore += $points;
            }

            $session->update([
                'score' => $totalScore,
                'max_score' => $maxScore,
                'status' => 'graded',
            ]);
        });
    }

    private function gradeSnapshotQuestion(array $questionSnapshot, Answer $answer): array
    {
        $selectedIds = collect($answer->selected_choice_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $choices = collect(data_get($questionSnapshot, 'choices', []));
        $correctIds = $choices
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if (data_get($questionSnapshot, 'type') === 'mcq_multi') {
            return $this->gradeMultiCorrectSnapshotQuestion($questionSnapshot, $selectedIds, $correctIds);
        }

        $isCorrect = $selectedIds === $correctIds;
        $points = $isCorrect ? (float) data_get($questionSnapshot, 'points', 0) : 0.0;

        return [$isCorrect, $points];
    }

    private function gradeMultiCorrectSnapshotQuestion(array $questionSnapshot, array $selectedIds, array $correctIds): array
    {
        $isCorrect = $selectedIds === $correctIds;
        $maxPoints = (float) data_get($questionSnapshot, 'points', 0);

        if ($isCorrect) {
            return [true, $maxPoints];
        }

        $gradingMode = data_get($questionSnapshot, 'settings.mcq_multi_grading', 'all_or_nothing');

        if ($gradingMode === 'all_or_nothing') {
            return [false, 0.0];
        }

        $selected = collect($selectedIds);
        $correct = collect($correctIds);
        $allChoiceIds = collect(data_get($questionSnapshot, 'choices', []))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $correctlySelected = $selected->intersect($correct)->count();
        $wronglySelected = $selected->diff($correct)->count();
        $correctCount = max(1, $correct->count());
        $wrongCount = max(1, $allChoiceIds->diff($correct)->count());

        $earned = $correctlySelected / $correctCount;

        if ($gradingMode === 'partial_with_deduction') {
            $earned -= $wronglySelected / $wrongCount;
        }

        $earned = max(0, min(1, $earned));

        return [false, round($maxPoints * $earned, 2)];
    }

    public function gradeQuestion(Question $question, Answer $answer): array
    {
        $selectedIds = $answer->selected_choice_ids ?? [];
        $correctIds  = $question->choices
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $selectedIds = collect($selectedIds)
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($question->type === 'mcq_multi') {
            return $this->gradeMultiCorrectQuestion($question, $selectedIds, $correctIds);
        }

        $isCorrect = $selectedIds === $correctIds;
        $points    = $isCorrect ? (float) $question->points : 0.0;

        return [$isCorrect, $points];
    }

    private function gradeMultiCorrectQuestion(Question $question, array $selectedIds, array $correctIds): array
    {
        $isCorrect = $selectedIds === $correctIds;
        $maxPoints = (float) $question->points;

        if ($isCorrect) {
            return [true, $maxPoints];
        }

        $gradingMode = $question->setting('mcq_multi_grading', 'all_or_nothing');

        if ($gradingMode === 'all_or_nothing') {
            return [false, 0.0];
        }

        $selected = collect($selectedIds);
        $correct = collect($correctIds);
        $allChoiceIds = $question->choices->pluck('id')->map(fn ($id) => (int) $id);

        $correctlySelected = $selected->intersect($correct)->count();
        $wronglySelected = $selected->diff($correct)->count();
        $correctCount = max(1, $correct->count());
        $wrongCount = max(1, $allChoiceIds->diff($correct)->count());

        $earned = $correctlySelected / $correctCount;

        if ($gradingMode === 'partial_with_deduction') {
            $earned -= $wronglySelected / $wrongCount;
        }

        $earned = max(0, min(1, $earned));

        return [false, round($maxPoints * $earned, 2)];
    }

    public function manualOverride(
        Answer $answer,
        float $pointsAwarded,
        User $gradedBy
    ): void {
        $answer->update([
            'points_awarded'     => $pointsAwarded,
            'is_correct'         => $pointsAwarded > 0,
            'is_manually_graded' => true,
            'graded_by'          => $gradedBy->id,
            'graded_at'          => now(),
        ]);

        // Recalculate session total
        $session = $answer->session()->with([
            'answers',
            'assignment.quiz.questions' => fn ($query) => $query->where('is_enabled', true),
        ])->first();
        $totalScore = $session->answers->sum(fn ($a) => (float) ($a->points_awarded ?? 0));
        $maxScore = (float) $session->assignment->quiz->questions->sum('points');

        $session->update([
            'score' => $totalScore,
            'max_score' => $maxScore,
        ]);
    }
}
