<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Model\Auth\LoginAttempt;

/** Registro de inicios de sesión, correctos y fallidos. */
interface LoginLog
{
    public function record(LoginAttempt $attempt): void;

    /** @return int cuántos se han borrado */
    public function purgeBefore(\DateTimeImmutable $limit): int;
}
