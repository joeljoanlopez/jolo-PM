<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Port\AccountRepository;
use App\Domain\Port\Clock;

/** En qué paso está una sesión: configuración inicial, contraseña, segundo factor o dentro. */
final class SessionStatus
{
    public const SETUP = 'setup';
    public const LOGIN = 'login';
    public const TWO_FACTOR = 'two_factor';
    public const AUTHENTICATED = 'authenticated';

    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @param ?string $authenticatedAccountId la cuenta con la que se inició la sesión, si se inició
     * @return array{state: string, recoveryCodesLeft?: int}
     */
    public function describe(?string $authenticatedAccountId, ?PendingLogin $pending): array
    {
        $account = $this->accounts->get();
        if ($account === null) {
            return ['state' => self::SETUP];
        }
        if ($authenticatedAccountId !== null && hash_equals($account->id, $authenticatedAccountId)) {
            return ['state' => self::AUTHENTICATED, 'recoveryCodesLeft' => $account->recoveryCodes->remaining()];
        }
        if ($pending !== null && $pending->accountId === $account->id && $this->clock->now() <= $pending->expiresAt) {
            return ['state' => self::TWO_FACTOR];
        }

        return ['state' => self::LOGIN];
    }

    public function isAuthenticated(?string $authenticatedAccountId): bool
    {
        return $this->describe($authenticatedAccountId, null)['state'] === self::AUTHENTICATED;
    }
}
