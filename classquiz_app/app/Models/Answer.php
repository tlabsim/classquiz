<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id', 'question_id', 'selected_choice_ids',
        'is_correct', 'points_awarded',
        'is_manually_graded', 'graded_by', 'graded_at', 'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_choice_ids' => 'array',
            'is_correct'          => 'boolean',
            'is_manually_graded'  => 'boolean',
            'points_awarded'      => 'decimal:2',
            'graded_at'           => 'datetime',
            'saved_at'            => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class, 'session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
