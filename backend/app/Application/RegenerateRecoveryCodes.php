<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Auth\RecoveryCodes;
use App\Domain\Auth\TotpVerifier;
use App\Domain\Exception\InvalidCredentials;
use App\Domain\Exception\LoginRequired;
use App\Domain\Exception\TooManyAttempts;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\LoginAttemptLimiter;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\SecretCipher;

/**
 * Sustituye los códigos de recuperación por 10 nuevos; los anteriores dejan de
 * valer. Pide un código TOTP aunque haya sesión: con una sesión ajena abierta
 * no basta para quedarse los códigos. Cuenta en el mismo límite de intentos
 * que el segundo paso del login.
 */
final class RegenerateRecoveryCodes
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly SecretCipher $cipher,
        private readonly TotpVerifier $totp,
        private readonly Clock $clock,
        private readonly LoginAttemptLimiter $limiter,
    ) {
    }

    /**
     * @return list<string> los códigos nuevos en claro, que solo se ven esta vez
     * @throws LoginRequired|InvalidCredentials|TooManyAttempts
     */
    public function execute(string $code, string $ip): array
    {
        $account = $this->accounts->get() ?? throw LoginRequired::unauthenticated();

        $key = 'two-factor:' . $ip;
        $wait = $this->limiter->blockedFor($key);
        if ($wait !== null) {
            throw new TooManyAttempts($wait);
        }

        $period = $this->totp->verify(
            $this->cipher->decrypt($account->encryptedTotpSecret),
            $code,
            $this->clock->now()->getTimestamp(),
            $account->lastTotpPeriod,
        );
        if ($period === null) {
            $this->limiter->recordFailure($key);
            throw InvalidCredentials::secondFactor();
        }
        $this->limiter->clear($key);

        [$plain, $codes] = RecoveryCodes::generate($this->hasher);
        $this->accounts->save($account->withLastTotpPeriod($period)->withRecoveryCodes($codes));

        return $plain;
    }
}
