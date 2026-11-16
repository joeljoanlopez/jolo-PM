<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Auth\PruneLoginLog;
use Illuminate\Console\Command;

final class PruneLoginLogCommand extends Command
{
    protected $signature = 'auth:prune-logins';

    protected $description = 'Borra los registros de inicio de sesión más antiguos que LOGIN_LOG_DAYS';

    public function handle(PruneLoginLog $prune): int
    {
        $deleted = $prune->execute((int) config('jolopm.auth.login_log_days'));
        $this->info("$deleted registros de inicio de sesión purgados.");

        return self::SUCCESS;
    }
}
