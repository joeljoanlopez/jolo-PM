<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Auth\Account;
use App\Domain\Auth\PasswordPolicy;
use App\Domain\Auth\RecoveryCodes;
use App\Domain\Auth\TotpVerifier;
use App\Domain\Exception\InvalidCredentials;
use App\Domain\Exception\SetupNotAvailable;
use App\Domain\Exception\TooManyAttempts;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\LoginAttemptLimiter;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\SecretCipher;

/**
 * Asistente de configuración inicial: código de los logs → contraseña → alta
 * del 2FA → códigos de recuperación. Cada paso vuelve a pedir el código de
 * configuración, y la cuenta solo se crea en el último.
 */
final class CompleteSetup
{
    public const ISSUER = 'jolo-PM';

    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly SecretCipher $cipher,
        private readonly TotpVerifier $totp,
        private readonly Clock $clock,
        private readonly LoginAttemptLimiter $limiter,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->accounts->get() === null;
    }

    /**
     * Paso 1.
     *
     * @throws SetupNotAvailable|InvalidCredentials|TooManyAttempts
     */
    public function checkCode(string $setupCode, string $ip): void
    {
        if ($this->accounts->get() !== null) {
            throw new SetupNotAvailable();
        }

        $key = 'setup:' . $ip;
        $wait = $this->limiter->blockedFor($key);
        if ($wait !== null) {
            throw new TooManyAttempts($wait);
        }

        $stored = $this->accounts->setupCode();
        if ($stored === null || !$stored->matches($setupCode)) {
            $this->limiter->recordFailure($key);
            throw InvalidCredentials::setupCode();
        }
        $this->limiter->clear($key);
    }

    /** Paso 2: valida la contraseña y propone un secreto TOTP. */
    public function choosePassword(string $setupCode, string $password, string $confirmation, string $ip): TwoFactorEnrollment
    {
        $this->checkCode($setupCode, $ip);
        PasswordPolicy::validate($password, $confirmation);

        $secret = $this->totp->generateSecret();

        return new TwoFactorEnrollment(
            $this->hasher->hash($password),
            $this->cipher->encrypt($secret),
            $secret,
            $this->totp->provisioningUri($secret, self::ISSUER, self::ISSUER),
        );
    }

    /**
     * Paso 3: con un código válido del secreto propuesto, crea la cuenta y
     * gasta el código de configuración. Devuelve los códigos de recuperación
     * en claro, que solo se ven esta vez (paso 4).
     *
     * @return array{account: Account, recoveryCodes: list<string>}
     */
    public function confirmTwoFactor(string $setupCode, TwoFactorEnrollment $enrollment, string $code, string $ip): array
    {
        $this->checkCode($setupCode, $ip);

        $key = 'two-factor:' . $ip;
        $wait = $this->limiter->blockedFor($key);
        if ($wait !== null) {
            throw new TooManyAttempts($wait);
        }
        $period = $this->totp->verify(
            $this->cipher->decrypt($enrollment->encryptedSecret),
            $code,
            $this->clock->now()->getTimestamp(),
            null,
        );
        if ($period === null) {
            $this->limiter->recordFailure($key);
            throw InvalidCredentials::secondFactor();
        }

        [$plainCodes, $recoveryCodes] = RecoveryCodes::generate($this->hasher);
        $account = Account::create($enrollment->passwordHash, $enrollment->encryptedSecret, $period, $recoveryCodes);
        $this->accounts->save($account);
        $this->accounts->deleteSetupCode();
        $this->limiter->clear($key);

        return ['account' => $account, 'recoveryCodes' => $plainCodes];
    }
}
