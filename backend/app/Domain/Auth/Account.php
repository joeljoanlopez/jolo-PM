<?php

declare(strict_types=1);

namespace App\Domain\Auth;

/**
 * La única cuenta de la app. No tiene nombre: se entra con contraseña y un
 * segundo factor, que es obligatorio.
 *
 * `id` es aleatorio y cambia si la cuenta se borra y se vuelve a crear, así
 * las sesiones de la cuenta anterior dejan de valer.
 */
final class Account
{
    public function __construct(
        public readonly string $id,
        public readonly string $passwordHash,
        /** Secreto TOTP cifrado con el puerto SecretCipher; nunca en claro. */
        public readonly string $encryptedTotpSecret,
        /** Último periodo TOTP aceptado: los códigos de ese periodo o anteriores ya no valen. */
        public readonly ?int $lastTotpPeriod,
        public readonly RecoveryCodes $recoveryCodes,
    ) {
    }

    public static function create(string $passwordHash, string $encryptedTotpSecret, int $totpPeriod, RecoveryCodes $recoveryCodes): self
    {
        return new self(bin2hex(random_bytes(16)), $passwordHash, $encryptedTotpSecret, $totpPeriod, $recoveryCodes);
    }

    public function withLastTotpPeriod(int $period): self
    {
        return new self($this->id, $this->passwordHash, $this->encryptedTotpSecret, $period, $this->recoveryCodes);
    }

    public function withRecoveryCodes(RecoveryCodes $codes): self
    {
        return new self($this->id, $this->passwordHash, $this->encryptedTotpSecret, $this->lastTotpPeriod, $codes);
    }
}
