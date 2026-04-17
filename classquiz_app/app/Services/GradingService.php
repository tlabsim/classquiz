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
        $session->load('assignment.quiz.questions.choices', 'answers');

        DB::transaction(function () use ($session) {
            $totalScore = 0;
            $maxScore   = 0;

            foreach ($session->assignment->quiz->questions as $question) {
                $maxScore += (float) $question->points;
                $answer = $session->answers->firstWhere('question_id', $question->id);

                if (!$answer || $answer->is_manually_graded) {
                    $totalScore += (float) ($answer?->points_awarded ?? 0);
                    continue;
                }

                [$isCorrect, $points] = $this->gradeQuestion($question, $answer);

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
        $session = $answer->session()->with('answers')->first();
        $totalScore = $session->answers->sum(fn ($a) => (float) ($a->points_awarded ?? 0));

        $session->update(['score' => $totalScore]);
    }
}
