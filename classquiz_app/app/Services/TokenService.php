<?php

namespace App\Services;

use App\Models\QuizSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class TokenService
{
    private int $ttlMinutes;

    public function __construct()
    {
        $this->ttlMinutes = (int) config('quiz.resume_token_ttl_minutes', 60);
    }

    public function generateResumeToken(QuizSession $session): string
    {
        $plain = Str::random(40);

        $session->update([
            'resume_token' => Hash::make($plain),
        ]);

        return $plain;
    }

    public function verifyResumeToken(QuizSession $session, string $plain): bool
    {
        if (!$session->resume_token) {
            return false;
        }

        return Hash::check($plain, $session->resume_token);
    }

    public function buildResumeUrl(QuizSession $session, string $plainToken): string
    {
        return URL::signedRoute('quiz.resume', [
            'session' => $session->id,
            'token'   => $plainToken,
        ], now()->addMinutes($this->ttlMinutes));
    }
}
