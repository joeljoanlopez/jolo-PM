<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Exception\AuthenticationFailed;
use App\Domain\Exception\NotAuthenticated;
use App\Domain\Model\Auth\Account;
use App\Domain\Model\Auth\LoginAttempt;
use App\Domain\Model\Auth\LoginStep;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\LoginLog;
use App\Domain\Port\SecretCipher;

/**
 * Segundo paso del login: un código TOTP o uno de recuperación. Cada código
 * vale una sola vez. Quien llama, si sale bien, da la sesión por autenticada.
 */
final class VerifySecondFactor
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly SecretCipher $cipher,
        private readonly Throttle $throttle,
        private readonly LoginLog $log,
        private readonly Clock $clock,
    ) {
    }

    /** @param string $accountId la cuenta con la que se pasó la contraseña */
    public function withTotp(string $accountId, string $code, string $ip): void
    {
        $this->verify($accountId, $ip, LoginStep::Totp, function (Account $account) use ($code) {
            $secret = $this->cipher->decrypt($account->encryptedTotpSecret);

            return $account->acceptTotp($secret, $code, $this->clock->now())
                ?? throw AuthenticationFailed::secondFactor();
        });
    }

    /** @return int cuántos códigos de recuperación quedan */
    public function withRecoveryCode(string $accountId, string $code, string $ip): int
    {
        return $this->verify(
            $accountId,
            $ip,
            LoginStep::RecoveryCode,
            fn (Account $account) => $account->consumeRecoveryCode($code) ?? throw AuthenticationFailed::recoveryCode(),
        )->recoveryCodes->remaining();
    }

    /** @param callable(Account): Account $check devuelve la cuenta con el código gastado */
    private function verify(string $accountId, string $ip, LoginStep $step, callable $check): Account
    {
        $account = $this->accounts->find();
        // La cuenta se ha borrado o recreado (auth:reset) entre los dos pasos.
        if ($account === null || $account->id !== $accountId) {
            throw NotAuthenticated::loginExpired();
        }

        try {
            $account = $this->throttle->attempt(Throttle::TWO_FACTOR, $ip, fn () => $check($account));
        } catch (AuthenticationFailed $e) {
            $this->log->record(new LoginAttempt($ip, false, $step, $this->clock->now()));
            throw $e;
        }

        $this->accounts->save($account);
        $this->log->record(new LoginAttempt($ip, true, $step, $this->clock->now()));

        return $account;
    }
}
