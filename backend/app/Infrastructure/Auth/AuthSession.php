<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Application\PendingLogin;
use App\Application\TwoFactorEnrollment;
use Illuminate\Contracts\Session\Session;

/**
 * Lo que la sesión de Laravel guarda del login: con qué cuenta se inició, el
 * login pendiente del segundo factor y el alta del 2FA a medio hacer.
 */
final class AuthSession
{
    private const ACCOUNT = 'auth.account';
    private const PENDING = 'auth.pending';
    private const ENROLLMENT = 'auth.enrollment';

    public function __construct(private readonly Session $session)
    {
    }

    public function authenticatedAccountId(): ?string
    {
        $id = $this->session->get(self::ACCOUNT);

        return is_string($id) ? $id : null;
    }

    public function pending(): ?PendingLogin
    {
        $pending = $this->session->get(self::PENDING);
        if (!is_array($pending) || !isset($pending['account'], $pending['expires'])) {
            return null;
        }

        return new PendingLogin((string) $pending['account'], (new \DateTimeImmutable())->setTimestamp((int) $pending['expires']));
    }

    /** Contraseña correcta: la sesión queda pendiente del segundo factor, no autenticada. */
    public function startPending(PendingLogin $pending): void
    {
        $this->session->migrate(true);
        $this->session->forget(self::ACCOUNT);
        $this->session->put(self::PENDING, ['account' => $pending->accountId, 'expires' => $pending->expiresAt->getTimestamp()]);
    }

    /** Sesión iniciada: id y token CSRF nuevos, para que no valgan los de antes del login. */
    public function authenticate(string $accountId): void
    {
        $this->session->forget([self::PENDING, self::ENROLLMENT]);
        $this->session->migrate(true);
        $this->session->regenerateToken();
        $this->session->put(self::ACCOUNT, $accountId);
    }

    public function enrollment(): ?TwoFactorEnrollment
    {
        $enrollment = $this->session->get(self::ENROLLMENT);
        if (!is_array($enrollment) || !isset($enrollment['password'], $enrollment['secret'])) {
            return null;
        }

        return new TwoFactorEnrollment((string) $enrollment['password'], (string) $enrollment['secret']);
    }

    /** Guarda el hash de la contraseña y el secreto cifrado, nunca el secreto en claro. */
    public function startEnrollment(TwoFactorEnrollment $enrollment): void
    {
        $this->session->put(self::ENROLLMENT, ['password' => $enrollment->passwordHash, 'secret' => $enrollment->encryptedSecret]);
    }

    public function logout(): void
    {
        $this->session->invalidate();
        $this->session->regenerateToken();
    }
}
