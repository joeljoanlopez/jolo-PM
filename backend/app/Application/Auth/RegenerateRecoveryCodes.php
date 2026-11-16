<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Exception\AuthenticationFailed;
use App\Domain\Exception\NotAuthenticated;
use App\Domain\Model\Auth\RecoveryCodes;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;
use App\Domain\Port\SecretCipher;

/**
 * Códigos de recuperación nuevos (los anteriores dejan de valer). Pide un
 * código TOTP aunque haya sesión: con una sesión ajena abierta no basta.
 */
final class RegenerateRecoveryCodes
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly SecretCipher $cipher,
        private readonly Throttle $throttle,
        private readonly Clock $clock,
    ) {
    }

    /** @return list<string> los códigos nuevos en claro, para enseñarlos una vez */
    public function execute(string $totpCode, string $ip): array
    {
        $account = $this->accounts->find() ?? throw NotAuthenticated::noSession();

        $account = $this->throttle->attempt(Throttle::TWO_FACTOR, $ip, function () use ($account, $totpCode) {
            $secret = $this->cipher->decrypt($account->encryptedTotpSecret);

            return $account->acceptTotp($secret, $totpCode, $this->clock->now())
                ?? throw AuthenticationFailed::secondFactor();
        });

        [$codes, $plain] = RecoveryCodes::generate();
        $this->accounts->save($account->withRecoveryCodes($codes));

        return $plain;
    }
}
