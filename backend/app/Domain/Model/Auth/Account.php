<?php

declare(strict_types=1);

namespace App\Domain\Model\Auth;

use App\Domain\Service\Auth\TotpVerifier;

/**
 * La única cuenta de la app: contraseña y segundo factor obligatorio.
 *
 * `id` es aleatorio y cambia cada vez que se crea la cuenta: las sesiones lo
 * guardan, así que borrar la cuenta (`auth:reset`) invalida todas las sesiones
 * abiertas aunque luego se cree otra.
 */
final class Account
{
    public function __construct(
        public readonly string $id,
        public readonly string $passwordHash,
        /** Secreto TOTP cifrado con el puerto SecretCipher; nunca en claro. */
        public readonly string $encryptedTotpSecret,
        /** Último periodo TOTP aceptado, para no aceptar un código dos veces. */
        public readonly ?int $lastTotpPeriod,
        public readonly RecoveryCodes $recoveryCodes,
    ) {
    }

    public static function newId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function withLastTotpPeriod(int $period): self
    {
        return new self($this->id, $this->passwordHash, $this->encryptedTotpSecret, $period, $this->recoveryCodes);
    }

    public function withRecoveryCodes(RecoveryCodes $codes): self
    {
        return new self($this->id, $this->passwordHash, $this->encryptedTotpSecret, $this->lastTotpPeriod, $codes);
    }

    /**
     * La cuenta con el código apuntado como usado, o null si no vale (mal,
     * fuera de su ventana o ya usado). `$secret` es el secreto ya descifrado.
     */
    public function acceptTotp(string $secret, string $code, \DateTimeImmutable $now): ?self
    {
        $period = TotpVerifier::verify($secret, $code, $now, $this->lastTotpPeriod);

        return $period === null ? null : $this->withLastTotpPeriod($period);
    }

    /** La cuenta sin ese código de recuperación, o null si no es uno de los suyos. */
    public function consumeRecoveryCode(string $code): ?self
    {
        $remaining = $this->recoveryCodes->consume($code);

        return $remaining === null ? null : $this->withRecoveryCodes($remaining);
    }
}
