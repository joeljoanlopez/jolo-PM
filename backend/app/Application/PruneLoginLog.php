<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Port\Clock;
use App\Domain\Port\LoginLog;

/** Borra los registros de inicio de sesión de hace más de `$days` días. */
final class PruneLoginLog
{
    public function __construct(private readonly LoginLog $log, private readonly Clock $clock)
    {
    }

    /** @return int cuántos registros se han borrado */
    public function execute(int $days): int
    {
        return $this->log->purgeBefore($this->clock->now()->modify("-$days days"));
    }
}
