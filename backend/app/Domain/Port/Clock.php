<?php

declare(strict_types=1);

namespace App\Domain\Port;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
