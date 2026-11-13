<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\IssueSetupCode;
use Illuminate\Console\Command;

/**
 * Lo lanza el entrypoint del backend al arrancar: si no hay cuenta, escribe en
 * los logs (`docker compose logs backend`) el código para crearla.
 */
final class IssueSetupCodeCommand extends Command
{
    protected $signature = 'auth:setup-code';

    protected $description = 'Genera el código de configuración inicial si aún no hay cuenta';

    public function handle(IssueSetupCode $issue): int
    {
        $code = $issue->execute();
        if ($code === null) {
            return self::SUCCESS;
        }

        SetupCodeBanner::print($this, $code);

        return self::SUCCESS;
    }
}
