<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Auth\ResetAccount;
use Illuminate\Console\Command;

/**
 * Recuperar el acceso (contraseña, móvil o códigos perdidos) o cambiar la
 * contraseña o el dispositivo del 2FA: borra la cuenta, no los proyectos.
 */
final class ResetAccountCommand extends Command
{
    protected $signature = 'auth:reset {--force : No pedir confirmación}';

    protected $description = 'Borra la cuenta (no los proyectos) y vuelve al asistente de configuración inicial';

    public function handle(ResetAccount $reset): int
    {
        if (
            !$this->option('force')
            && !$this->confirm('Se borra la cuenta y se cierran todas las sesiones. ¿Seguir?', true)
        ) {
            return self::FAILURE;
        }

        $code = $reset->execute();
        $this->info('Cuenta borrada. Los proyectos siguen ahí.');
        IssueSetupCodeCommand::announce($this, $code);

        return self::SUCCESS;
    }
}
