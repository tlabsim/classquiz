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
        $secure = $this->shouldUseSecureCookies();

        return cookie(
            $cookieName,
            $sessionId,
            60 * 24, // 24 hours
            config('session.path', '/'),
            config('session.domain'),
            $secure,
            config('session.http_only', true),
            false,
            config('session.same_site', 'lax'),
        );
    }

    public function hasOwnership(Request $request, QuizSession $session): bool
    {
        $cookieName = self::COOKIE_PREFIX . $session->id;
        return $request->cookie($cookieName) === $session->id;
    }

    public function clearCookie(string $sessionId): Cookie
    {
        return cookie()->forget(
            self::COOKIE_PREFIX . $sessionId,
            config('session.path', '/'),
            config('session.domain'),
            $this->shouldUseSecureCookies(),
            config('session.same_site', 'lax'),
        );
    }

    private function shouldUseSecureCookies(): bool
    {
        $configured = config('session.secure');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return request()?->isSecure() ?? false;
    }
}
