<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

/**
 * Stricter, admin-scoped login limiter.
 *
 * Extends Fortify's LoginRateLimiter to:
 *   - Scope cache keys with an "admin:" prefix so frontend and admin attempts
 *     are tracked separately.
 *   - Cap admin attempts at 3 per minute (vs Fortify's default 5) given the
 *     higher blast radius of a successful admin compromise.
 */
class AdminLoginRateLimiter extends LoginRateLimiter
{
    private const MAX_ATTEMPTS = 3;
    private const DECAY_SECONDS = 60;

    public function tooManyAttempts(Request $request): bool
    {
        return $this->limiter->tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS);
    }

    public function increment(Request $request): void
    {
        $this->limiter->hit($this->throttleKey($request), self::DECAY_SECONDS);
    }

    protected function throttleKey(Request $request): string
    {
        return 'admin:' . Str::transliterate(
            Str::lower((string) $request->input(Fortify::username())) . '|' . $request->ip(),
        );
    }
}
