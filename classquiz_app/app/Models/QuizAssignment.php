<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAssignment extends Model
{
    const SETTINGS_DEFAULTS = [
        'allow_resume'       => true,
        'show_score'         => true,
        'randomize_questions' => false,
        'max_attempts'       => 1,
    ];

    protected $fillable = [
        'quiz_id', 'public_token', 'is_active', 'title', 'instructions',
        'registration_start', 'registration_end',
        'availability_start', 'availability_end',
        'duration_minutes', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'settings'           => 'array',
            'registration_start' => 'datetime',
            'registration_end'   => 'datetime',
            'availability_start' => 'datetime',
            'availability_end'   => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuizSession::class, 'assignment_id');
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        if (array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        return self::SETTINGS_DEFAULTS[$key] ?? $default;
    }

    public function displayTitle(): string
    {
        return $this->title ?? $this->quiz->title;
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->availability_start && $now->lt($this->availability_start)) {
            return false;
        }

        if ($this->availability_end && $now->gt($this->availability_end)) {
            return false;
        }

        return true;
    }

    public function isRegistrationOpen(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->registration_start && $now->lt($this->registration_start)) {
            return false;
        }

        if ($this->registration_end && $now->gt($this->registration_end)) {
            return false;
        }

        return true;
    }
}
