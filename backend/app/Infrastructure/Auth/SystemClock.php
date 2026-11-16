<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Port\Clock;
use Illuminate\Support\Facades\Date;

/** La hora de Laravel, para que los tests puedan fijarla con travelTo(). */
final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return Date::now()->toDateTimeImmutable();
    }
}
