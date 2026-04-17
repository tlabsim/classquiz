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
        'status', 'question_order',
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
            'score'                  => 'decimal:2',
            'max_score'              => 'decimal:2',
        ];
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
        return $this->access_code_expires_at->isPast();
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
}
