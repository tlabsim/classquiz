<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSession extends Model
{
    use HasUlids;

    protected $fillable = [
        'assignment_id', 'email', 'name', 'class_id',
        'access_code', 'access_code_expires_at', 'resume_token',
        'status', 'question_order', 'quiz_snapshot',
        'started_at', 'last_activity_at', 'submitted_at',
        'score', 'max_score',
    ];

    protected $hidden = ['access_code', 'resume_token'];

    protected function casts(): array
    {
        return [
            'access_code_expires_at' => 'datetime',
            'started_at'             => 'datetime',
            'last_activity_at'       => 'datetime',
            'submitted_at'           => 'datetime',
            'question_order'         => 'array',
            'quiz_snapshot'          => 'array',
            'score'                  => 'decimal:2',
            'max_score'              => 'decimal:2',
        ];
    }

    protected function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value !== null
            ? strtolower(trim($value))
            : null;
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(QuizAssignment::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'session_id');
    }

    public function isAccessCodeExpired(): bool
    {
        return $this->access_code_expires_at?->isPast() ?? false;
    }

    public function hasStarted(): bool
    {
        return $this->started_at !== null;
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded']);
    }

    public function isTimedOut(): bool
    {
        if (!$this->hasStarted()) {
            return false;
        }

        $durationMinutes = $this->assignment->duration_minutes;

        if (!$durationMinutes) {
            return false;
        }

        return $this->started_at->addMinutes($durationMinutes)->isPast();
    }

    public function effectiveMaxScore(): float
    {
        if (!empty($this->quiz_snapshot['questions'])) {
            return (float) collect($this->quiz_snapshot['questions'])->sum('points');
        }

        if (
            $this->relationLoaded('assignment')
            && $this->assignment
            && $this->assignment->relationLoaded('quiz')
            && $this->assignment->quiz
            && $this->assignment->quiz->relationLoaded('questions')
        ) {
            return (float) $this->assignment->quiz->questions->sum('points');
        }

        return (float) $this->assignment()
            ->with(['quiz.questions' => fn ($query) => $query->where('is_enabled', true)])
            ->first()
            ?->quiz
            ?->questions
            ->sum('points');
    }

    public function buildQuizSnapshot($questions, $answers = null): array
    {
        $this->loadMissing('assignment.quiz');

        $questionSnapshots = collect($questions)
            ->filter()
            ->values()
            ->map(function ($question) {
                return [
                    'id' => (int) $question->id,
                    'tag' => $question->tag,
                    'type' => $question->type,
                    'text' => $question->text,
                    'points' => (float) $question->points,
                    'settings' => $question->settings ?? [],
                    'feedback_correct' => $question->feedback_correct,
                    'feedback_incorrect' => $question->feedback_incorrect,
                    'explanation' => $question->explanation,
                    'choices' => $question->choices
                        ->map(fn ($choice) => [
                            'id' => (int) $choice->id,
                            'text' => $choice->text,
                            'is_correct' => (bool) $choice->is_correct,
                            'sort_order' => (int) $choice->sort_order,
                        ])
                        ->values()
                        ->all(),
                ];
            });

        $answersByQuestionId = collect($answers)->keyBy('question_id');
        $answerSnapshots = $questionSnapshots->map(function (array $question) use ($answersByQuestionId) {
            $answer = $answersByQuestionId->get($question['id']);

            return [
                'question_id' => $question['id'],
                'selected_choice_ids' => collect($answer?->selected_choice_ids ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
                'is_correct' => $answer?->is_correct,
                'points_awarded' => $answer?->points_awarded !== null ? (float) $answer->points_awarded : null,
                'is_manually_graded' => (bool) ($answer?->is_manually_graded ?? false),
                'saved_at' => $answer?->saved_at?->toIso8601String(),
                'graded_at' => $answer?->graded_at?->toIso8601String(),
            ];
        })->values();

        return [
            'captured_at' => now()->toIso8601String(),
            'assignment' => [
                'id' => (int) $this->assignment_id,
                'title' => $this->assignment?->displayTitle(),
                'public_token' => $this->assignment?->public_token,
                'duration_minutes' => $this->assignment?->duration_minutes,
                'availability_start' => $this->assignment?->availability_start?->toIso8601String(),
                'availability_end' => $this->assignment?->availability_end?->toIso8601String(),
                'access_code_required' => (bool) ($this->assignment?->access_code_required ?? false),
                'settings' => $this->assignment?->settings ?? [],
                'quiz_id' => $this->assignment?->quiz_id,
                'quiz_title' => $this->assignment?->quiz?->title,
            ],
            'question_order' => $questionSnapshots->pluck('id')->all(),
            'questions' => $questionSnapshots->all(),
            'answers' => $answerSnapshots->all(),
        ];
    }

    public function hasQuizSnapshot(): bool
    {
        return !empty($this->quiz_snapshot['questions']);
    }

    public function hasLegacyChoiceMismatch(): bool
    {
        if ($this->hasQuizSnapshot()) {
            return false;
        }

        $this->loadMissing('answers.question.choices');

        return $this->answers->contains(function ($answer) {
            $selectedIds = collect($answer->selected_choice_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if ($selectedIds->isEmpty()) {
                return false;
            }

            $question = $answer->question;

            if (!$question || !$question->hasChoices()) {
                return false;
            }

            $currentChoiceIds = $question->choices
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            return $currentChoiceIds->isEmpty() || $selectedIds->intersect($currentChoiceIds)->isEmpty();
        });
    }
}
