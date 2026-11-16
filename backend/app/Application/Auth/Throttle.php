<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Exception\AuthenticationFailed;
use App\Domain\Exception\TooManyAttempts;
use App\Domain\Port\LoginAttemptLimiter;

/**
 * Límite de intentos por acción e IP: cuenta cada AuthenticationFailed y,
 * pasado el límite, rechaza con TooManyAttempts sin llegar a comprobar nada.
 * Un intento correcto pone la cuenta a cero.
 */
final class Throttle
{
    public const LOGIN = 'login';
    public const TWO_FACTOR = 'two-factor';
    public const SETUP = 'setup';

    public function __construct(private readonly LoginAttemptLimiter $limiter)
    {
    }

    /**
     * @template T
     * @param callable(): T $attempt
     * @return T
     * @throws TooManyAttempts antes de intentarlo, si ya se ha pasado del límite
     * @throws AuthenticationFailed lo que lance `$attempt`, que cuenta como fallo
     */
    public function attempt(string $action, string $ip, callable $attempt): mixed
    {
        $key = $action . '|' . $ip;
        $wait = $this->limiter->retryAfter($key);
        if ($wait !== null) {
            throw new TooManyAttempts($wait);
        }

        try {
            $result = $attempt();
        } catch (AuthenticationFailed $e) {
            $this->limiter->hit($key);
            throw $e;
        }
        $this->limiter->clear($key);

        return $result;
    }
}
