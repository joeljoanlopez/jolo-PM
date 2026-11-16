<?php

declare(strict_types=1);

namespace App\Domain\Model\Auth;

/** En qué paso del login se ha decidido un intento. */
enum LoginStep: string
{
    case Password = 'password';
    case Totp = 'totp';
    case RecoveryCode = 'recovery_code';
}
