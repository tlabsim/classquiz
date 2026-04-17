<?php

namespace App\Models;

use App\Support\QuestionTextSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAssignment extends Model
{
    private const PUBLIC_TOKEN_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    const SETTINGS_DEFAULTS = [
        'allow_resume'       => true,
        'show_score'         => true,
        'randomize_questions' => true,
        'question_presentation' => 'one_per_page',
        'allow_modify_previous_answers' => true,
        'collect_email'      => true,
        'collect_class_id'   => false,
        'collect_name'       => false,
        'timezone'           => 'UTC',
        'show_correct_answers' => false,
        'show_feedback_and_explanation' => false,
        'max_attempts'       => 1,
    ];

    protected $fillable = [
        'quiz_id', 'public_token', 'is_active', 'title', 'instructions',
        'availability_start', 'availability_end',
        'access_code_required', 'access_code_starts_before_minutes',
        'duration_minutes', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'access_code_required' => 'boolean',
            'access_code_starts_before_minutes' => 'integer',
            'settings'           => 'array',
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

    public static function normalizePublicToken(string $token): string
    {
        return strtoupper(trim($token));
    }

    public static function findByPublicToken(string $token): ?self
    {
        return self::query()
            ->where('public_token', self::normalizePublicToken($token))
            ->first();
    }

    public static function findByPublicTokenOrFail(string $token): self
    {
        return self::query()
            ->where('public_token', self::normalizePublicToken($token))
            ->firstOrFail();
    }

    public static function generatePublicToken(int $length = 10): string
    {
        $alphabet = self::PUBLIC_TOKEN_ALPHABET;
        $maxIndex = strlen($alphabet) - 1;

        do {
            $token = '';

            for ($i = 0; $i < $length; $i++) {
                $token .= $alphabet[random_int(0, $maxIndex)];
            }
        } while (self::query()->where('public_token', $token)->exists());

        return $token;
    }

    public function timezone(): string
    {
        $timezone = (string) $this->setting('timezone', config('app.timezone', 'UTC'));

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('app.timezone', 'UTC');
    }

    public function displayDateTime(?Carbon $value, string $format = 'M j, Y g:i A'): ?string
    {
        if (!$value) {
            return null;
        }

        return $value->copy()->timezone($this->timezone())->format($format);
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

        if (!$this->access_code_required) {
            return false;
        }

        $now = now();

        $accessCodeOpensAt = $this->accessCodeOpensAt();

        if ($accessCodeOpensAt && $now->lt($accessCodeOpensAt)) {
            return false;
        }

        if ($this->availability_end && $now->gt($this->availability_end)) {
            return false;
        }

        return true;
    }

    public function accessCodeOpensAt(): ?\Illuminate\Support\Carbon
    {
        if (!$this->availability_start) {
            return null;
        }

        return $this->availability_start->copy()->subMinutes((int) ($this->access_code_starts_before_minutes ?? 0));
    }

    protected function setInstructionsAttribute(?string $value): void
    {
        $this->attributes['instructions'] = QuestionTextSanitizer::sanitize($value);
    }
}
