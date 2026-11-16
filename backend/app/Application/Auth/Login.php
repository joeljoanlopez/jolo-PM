<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Exception\AuthenticationFailed;
use App\Domain\Exception\SetupState;
use App\Domain\Model\Auth\LoginAttempt;
use App\Domain\Model\Auth\LoginStep;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\LoginLog;
use App\Domain\Port\PasswordHasher;

/**
 * Primer paso del login: la contraseña. Si es correcta, quien llama deja la
 * sesión pendiente del segundo factor; todavía no da acceso a nada.
 */
final class Login
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly Throttle $throttle,
        private readonly LoginLog $log,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @return string id de la cuenta, para atar la sesión a ella
     * @throws SetupState|AuthenticationFailed|\App\Domain\Exception\TooManyAttempts
     */
    public function execute(string $password, string $ip): string
    {
        $account = $this->accounts->find() ?? throw SetupState::required();

        try {
            $this->throttle->attempt(Throttle::LOGIN, $ip, function () use ($password, $account) {
                if (!$this->hasher->verify($password, $account->passwordHash)) {
                    throw AuthenticationFailed::password();
                }
            });
        } catch (AuthenticationFailed $e) {
            $this->log->record(new LoginAttempt($ip, false, LoginStep::Password, $this->clock->now()));
            throw $e;
        }

        return $account->id;
    }
}
