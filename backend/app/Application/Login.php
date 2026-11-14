<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Auth\LoginAttempt;
use App\Domain\Exception\InvalidCredentials;
use App\Domain\Exception\SetupRequired;
use App\Domain\Exception\TooManyAttempts;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\LoginAttemptLimiter;
use App\Domain\Port\LoginLog;
use App\Domain\Port\PasswordHasher;

/** Primer paso del login: la contraseña. No da acceso; deja pendiente el segundo factor. */
final class Login
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly Clock $clock,
        private readonly LoginAttemptLimiter $limiter,
        private readonly LoginLog $log,
    ) {
    }

    /** @throws SetupRequired|InvalidCredentials|TooManyAttempts */
    public function execute(string $password, string $ip): PendingLogin
    {
        $account = $this->accounts->get();
        if ($account === null) {
            throw new SetupRequired();
        }

        $key = 'login:' . $ip;
        $wait = $this->limiter->blockedFor($key);
        if ($wait !== null) {
            throw new TooManyAttempts($wait);
        }

        $now = $this->clock->now();
        $ok = $this->hasher->verify($password, $account->passwordHash);
        $this->log->record(new LoginAttempt($ip, LoginAttempt::PASSWORD, $ok, $now));
        if (!$ok) {
            $this->limiter->recordFailure($key);
            throw InvalidCredentials::password();
        }
        $this->limiter->clear($key);

        return new PendingLogin($account->id, $now->modify('+' . PendingLogin::TTL_SECONDS . ' seconds'));
    }
}
