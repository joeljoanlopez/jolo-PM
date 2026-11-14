<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Port\LoginAttemptLimiter;
use Illuminate\Cache\RateLimiter;

/** Sobre el RateLimiter de Laravel, que guarda los contadores en la caché. */
final class LaravelLoginAttemptLimiter implements LoginAttemptLimiter
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly int $maxAttempts,
        private readonly int $decaySeconds,
    ) {
    }

    public function blockedFor(string $key): ?int
    {
        $key = 'auth:' . $key;

        return $this->limiter->tooManyAttempts($key, $this->maxAttempts)
            ? max(1, $this->limiter->availableIn($key))
            : null;
    }

    public function recordFailure(string $key): void
    {
        $this->limiter->hit('auth:' . $key, $this->decaySeconds);
    }

    public function clear(string $key): void
    {
        $this->limiter->clear('auth:' . $key);
    }
}
