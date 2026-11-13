<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Port\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return now()->toDateTimeImmutable();
    }
}
