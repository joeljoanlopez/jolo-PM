<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Port\Clock;

final class FrozenClock implements Clock
{
    public function __construct(public \DateTimeImmutable $now = new \DateTimeImmutable('2026-11-13 10:00:00'))
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(int $seconds): void
    {
        $this->now = $this->now->modify("+$seconds seconds");
    }
}
