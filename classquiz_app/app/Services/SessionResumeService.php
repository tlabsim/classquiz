<?php

namespace App\Services;

use App\Models\QuizSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class SessionResumeService
{
    private const COOKIE_PREFIX = 'quiz_session_';

    public function storeCookie(string $sessionId): Cookie
    {
        $cookieName = self::COOKIE_PREFIX . $sessionId;

        return cookie(
            $cookieName,
            $sessionId,
            60 * 24, // 24 hours
            '/',
            null,
            true,   // Secure
            true,   // HttpOnly
            false,
            'Strict'
        );
    }

    public function hasOwnership(Request $request, QuizSession $session): bool
    {
        $cookieName = self::COOKIE_PREFIX . $session->id;
        return $request->cookie($cookieName) === $session->id;
    }

    public function clearCookie(string $sessionId): Cookie
    {
        return cookie()->forget(self::COOKIE_PREFIX . $sessionId);
    }
}
