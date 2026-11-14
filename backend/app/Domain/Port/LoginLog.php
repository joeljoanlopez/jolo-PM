<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Auth\LoginAttempt;

/** Registro de cada inicio de sesión, correcto o fallido. */
interface LoginLog
{
    public function record(LoginAttempt $attempt): void;
}
