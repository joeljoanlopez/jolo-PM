<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Port\LoginAttemptLimiter;
use Illuminate\Cache\RateLimiter;

/** Límite de intentos sobre el RateLimiter de Laravel (en la caché, CACHE_STORE). */
final class CacheLoginAttemptLimiter implements LoginAttemptLimiter
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly int $maxAttempts,
        private readonly int $decaySeconds,
    ) {
    }

    public function retryAfter(string $key): ?int
    {
        $key = $this->key($key);
        if (!$this->limiter->tooManyAttempts($key, $this->maxAttempts)) {
            return null;
        }

        return max(1, $this->limiter->availableIn($key));
    }

    public function hit(string $key): void
    {
        $this->limiter->hit($this->key($key), $this->decaySeconds);
    }

    public function clear(string $key): void
    {
        $this->limiter->clear($this->key($key));
    }

    private function key(string $key): string
    {
        return 'auth:' . $key;
    }
}
