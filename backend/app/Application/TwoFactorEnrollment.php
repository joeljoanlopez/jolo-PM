<?php

declare(strict_types=1);

namespace App\Application;

/**
 * Alta del 2FA a medio hacer: la contraseña ya elegida y el secreto TOTP
 * propuesto, que no se guardan en la cuenta hasta confirmar un código.
 */
final class TwoFactorEnrollment
{
    public function __construct(
        public readonly string $passwordHash,
        public readonly string $encryptedSecret,
        /** Para enseñarlo una vez (QR y texto); no se guarda en claro. */
        public readonly ?string $secret = null,
        public readonly ?string $provisioningUri = null,
    ) {
    }
}
