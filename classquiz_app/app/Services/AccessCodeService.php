<?php

namespace App\Services;

use App\Models\QuizSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccessCodeService
{
    private int $ttlMinutes;

    public function __construct()
    {
        $this->ttlMinutes = (int) config('quiz.access_code_ttl_minutes', 15);
    }

    public function generate(): string
    {
        // 6-character uppercase alphanumeric code
        return strtoupper(Str::random(6));
    }

    public function store(QuizSession $session, string $plainCode): void
    {
        $session->update([
            'access_code'             => Hash::make($plainCode),
            'access_code_expires_at'  => now()->addMinutes($this->ttlMinutes),
        ]);
    }

    public function verify(QuizSession $session, string $plainCode): bool
    {
        if ($session->isAccessCodeExpired()) {
            return false;
        }

        return Hash::check($plainCode, $session->access_code);
    }

    public function regenerate(QuizSession $session): string
    {
        $code = $this->generate();
        $this->store($session, $code);
        return $code;
    }
}
