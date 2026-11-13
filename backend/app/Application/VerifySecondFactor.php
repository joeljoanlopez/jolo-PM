<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Auth\Account;
use App\Domain\Auth\LoginAttempt;
use App\Domain\Auth\TotpVerifier;
use App\Domain\Exception\InvalidCredentials;
use App\Domain\Exception\LoginRequired;
use App\Domain\Exception\TooManyAttempts;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\LoginAttemptLimiter;
use App\Domain\Port\LoginLog;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\SecretCipher;

/** Segundo paso del login: un código TOTP o uno de recuperación. */
final class VerifySecondFactor
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly SecretCipher $cipher,
        private readonly TotpVerifier $totp,
        private readonly Clock $clock,
        private readonly LoginAttemptLimiter $limiter,
        private readonly LoginLog $log,
    ) {
    }

    /**
     * Devuelve la cuenta en la que queda iniciada la sesión.
     *
     * @throws LoginRequired|InvalidCredentials|TooManyAttempts
     */
    public function execute(?PendingLogin $pending, ?string $code, ?string $recoveryCode, string $ip): Account
    {
        $now = $this->clock->now();
        $account = $this->accounts->get();
        if ($pending === null || $account === null || $account->id !== $pending->accountId || $now > $pending->expiresAt) {
            throw LoginRequired::secondFactorExpired();
        }

        $key = 'two-factor:' . $ip;
        $wait = $this->limiter->blockedFor($key);
        if ($wait !== null) {
            throw new TooManyAttempts($wait);
        }

        $updated = null;
        if ($recoveryCode !== null && $recoveryCode !== '') {
            $method = LoginAttempt::RECOVERY_CODE;
            $remaining = $account->recoveryCodes->consume($recoveryCode, $this->hasher);
            $updated = $remaining === null ? null : $account->withRecoveryCodes($remaining);
        } else {
            $method = LoginAttempt::TOTP;
            $period = $this->totp->verify(
                $this->cipher->decrypt($account->encryptedTotpSecret),
                (string) $code,
                $now->getTimestamp(),
                $account->lastTotpPeriod,
            );
            $updated = $period === null ? null : $account->withLastTotpPeriod($period);
        }

        $this->log->record(new LoginAttempt($ip, $method, $updated !== null, $now));
        if ($updated === null) {
            $this->limiter->recordFailure($key);
            throw InvalidCredentials::secondFactor();
        }

        $this->accounts->save($updated);
        $this->limiter->clear($key);

        return $updated;
    }
}
