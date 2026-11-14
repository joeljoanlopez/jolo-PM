<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Auth\RecoveryCodes;
use App\Domain\Exception\LoginRequired;
use App\Domain\Port\AccountRepository;
use App\Domain\Port\PasswordHasher;

/** Sustituye los códigos de recuperación por 10 nuevos; los anteriores dejan de valer. */
final class RegenerateRecoveryCodes
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
    ) {
    }

    /** @return list<string> los códigos nuevos en claro, que solo se ven esta vez */
    public function execute(): array
    {
        $account = $this->accounts->get() ?? throw LoginRequired::unauthenticated();

        [$plain, $codes] = RecoveryCodes::generate($this->hasher);
        $this->accounts->save($account->withRecoveryCodes($codes));

        return $plain;
    }
}
