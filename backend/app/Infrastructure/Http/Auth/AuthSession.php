<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Auth;

use App\Domain\Exception\NotAuthenticated;
use App\Domain\Port\AccountRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Date;

/**
 * Estado del login guardado en la sesión de Laravel. La sesión guarda el id
 * de la cuenta con la que se entró: si la cuenta se borra o se recrea
 * (`auth:reset`), la sesión deja de valer sola.
 *
 * El asistente de configuración también guarda aquí su estado (el código ya
 * comprobado y el secreto TOTP propuesto); la sesión va cifrada.
 */
final class AuthSession
{
    public const AUTHENTICATED = 'auth.account';
    public const PENDING = 'auth.pending';
    public const SETUP = 'auth.setup';

    /** Minutos para meter el segundo factor después de la contraseña. */
    public const PENDING_MINUTES = 5;

    public function __construct(
        private readonly Session $session,
        private readonly AccountRepository $accounts,
    ) {
    }

    /** setup | login | two_factor | authenticated */
    public function state(): string
    {
        $account = $this->accounts->find();
        if ($account === null) {
            return 'setup';
        }
        if ($this->session->get(self::AUTHENTICATED) === $account->id) {
            return 'authenticated';
        }
        if ($this->pendingAccountId() === $account->id) {
            return 'two_factor';
        }

        return 'login';
    }

    public function isAuthenticated(): bool
    {
        return $this->state() === 'authenticated';
    }

    /** Contraseña correcta: falta el segundo factor. */
    public function startPending(string $accountId): void
    {
        $this->session->forget(self::AUTHENTICATED);
        $this->session->put(self::PENDING, [
            'account' => $accountId,
            'expires' => Date::now()->addMinutes(self::PENDING_MINUTES)->getTimestamp(),
        ]);
        $this->session->regenerate(true);
    }

    /**
     * @return string la cuenta del login pendiente
     * @throws NotAuthenticated si no hay login pendiente o ha caducado
     */
    public function pendingAccount(): string
    {
        return $this->pendingAccountId() ?? throw NotAuthenticated::loginExpired();
    }

    /** Segundo factor correcto: sesión nueva (id y token CSRF) y autenticada. */
    public function authenticate(string $accountId): void
    {
        $this->session->forget([self::PENDING, self::SETUP]);
        $this->session->put(self::AUTHENTICATED, $accountId);
        $this->session->regenerate(true);
        $this->session->regenerateToken();
    }

    public function logout(): void
    {
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    /** @param array<string, string> $data */
    public function putSetup(array $data): void
    {
        $this->session->put(self::SETUP, $data);
    }

    /** @return array<string, string> */
    public function setup(): array
    {
        $data = $this->session->get(self::SETUP);

        return is_array($data) ? $data : [];
    }

    private function pendingAccountId(): ?string
    {
        $pending = $this->session->get(self::PENDING);
        if (!is_array($pending) || !is_string($pending['account'] ?? null)) {
            return null;
        }
        if ((int) ($pending['expires'] ?? 0) < Date::now()->getTimestamp()) {
            $this->session->forget(self::PENDING);

            return null;
        }

        return $pending['account'];
    }
}
