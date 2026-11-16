<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Exception\AuthenticationFailed;
use App\Domain\Exception\SetupState;
use App\Domain\Model\Auth\Account;
use App\Domain\Model\Auth\Password;
use App\Domain\Model\Auth\RecoveryCodes;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\PasswordHasher;
use App\Domain\Port\SecretCipher;
use App\Domain\Service\Auth\TotpVerifier;

/**
 * Asistente de configuración inicial: código de los logs → contraseña → alta
 * del TOTP → códigos de recuperación. Los primeros pasos solo validan; la
 * cuenta se crea entera en execute(), que vuelve a comprobarlo todo.
 */
final class CompleteSetup
{
    public const ISSUER = 'jolo-PM';

    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly SecretCipher $cipher,
        private readonly Clock $clock,
        private readonly Throttle $throttle,
    ) {
    }

    /** Paso 1: el código de los logs. Lanza SetupState si ya hay cuenta. */
    public function checkCode(string $setupCode, string $ip): void
    {
        $this->throttle->attempt(Throttle::SETUP, $ip, fn () => $this->assertCode($setupCode));
    }

    /** @throws \App\Domain\Exception\ValidationFailed */
    public function checkPassword(string $password, string $confirmation): void
    {
        Password::validate($password, $confirmation);
    }

    /** @return array{secret: string, uri: string} un secreto TOTP nuevo y su URI para el QR */
    public function newSecret(): array
    {
        $secret = TotpVerifier::generateSecret();

        return ['secret' => $secret, 'uri' => TotpVerifier::uri($secret, self::ISSUER, self::ISSUER)];
    }

    /**
     * Crea la cuenta. El código de configuración deja de valer.
     *
     * @return array{accountId: string, recoveryCodes: list<string>}
     */
    public function execute(
        string $setupCode,
        string $password,
        string $confirmation,
        string $secret,
        string $totpCode,
        string $ip,
    ): array {
        $this->assertCode($setupCode);
        Password::validate($password, $confirmation);

        $period = $this->throttle->attempt(Throttle::SETUP, $ip, function () use ($secret, $totpCode) {
            return TotpVerifier::verify($secret, $totpCode, $this->clock->now())
                ?? throw AuthenticationFailed::secondFactor();
        });

        [$recoveryCodes, $plain] = RecoveryCodes::generate();
        $account = new Account(
            Account::newId(),
            $this->hasher->hash($password),
            $this->cipher->encrypt($secret),
            $period,
            $recoveryCodes,
        );
        $this->accounts->save($account);
        $this->accounts->deleteSetupCode();

        return ['accountId' => $account->id, 'recoveryCodes' => $plain];
    }

    private function assertCode(string $setupCode): void
    {
        if ($this->accounts->find() !== null) {
            throw SetupState::alreadyDone();
        }
        if (!($this->accounts->findSetupCode()?->matches($setupCode) ?? false)) {
            throw AuthenticationFailed::setupCode();
        }
    }
}
