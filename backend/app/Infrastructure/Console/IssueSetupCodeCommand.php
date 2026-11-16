<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Auth\IssueSetupCode;
use Illuminate\Console\Command;

/**
 * Lo lanza el entrypoint del backend al arrancar: sin cuenta, escribe en los
 * logs del contenedor un código de configuración nuevo. Con cuenta, nada.
 */
final class IssueSetupCodeCommand extends Command
{
    protected $signature = 'auth:setup-code';

    protected $description = 'Sin cuenta creada, genera el código de la configuración inicial';

    public function handle(IssueSetupCode $issue): int
    {
        $code = $issue->execute();
        if ($code !== null) {
            self::announce($this, $code);
        }

        return self::SUCCESS;
    }

    public static function announce(Command $command, string $code): void
    {
        $command->line('');
        $command->line('  jolo-PM no tiene cuenta todavía. Abre la web y usa este código de configuración:');
        $command->line('');
        $command->line("      $code");
        $command->line('');
        $command->line('  Vale una sola vez; al reiniciar el backend se genera otro.');
        $command->line('');
    }
}
