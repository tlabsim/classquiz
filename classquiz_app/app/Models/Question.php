<?php

namespace App\Models;

use App\Support\QuestionTextSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    const SETTINGS_DEFAULTS = [
        'randomize_choices' => true,
        'rich_choices' => false,
        'mcq_multi_grading' => 'all_or_nothing',
    ];

    // Types that don't have selectable choices
    const OPEN_TYPES = ['short_answer'];

    protected $fillable = [
        'quiz_id',
        'tag',
        'type',
        'text',
        'points',
        'sort_order',
        'is_enabled',
        'settings',
        'feedback_correct',
        'feedback_incorrect',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'settings'   => 'array',
            'points'     => 'decimal:2',
            'is_enabled' => 'boolean',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class)->orderBy('sort_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        if (array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        return self::SETTINGS_DEFAULTS[$key] ?? $default;
    }

    public function hasChoices(): bool
    {
        return !in_array($this->type, self::OPEN_TYPES);
    }

    protected function setTextAttribute(?string $value): void
    {
        $this->attributes['text'] = QuestionTextSanitizer::sanitize($value);
    }
}
