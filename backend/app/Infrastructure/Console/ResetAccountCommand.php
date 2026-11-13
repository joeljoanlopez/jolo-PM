<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\ResetAccount;
use Illuminate\Console\Command;

/**
 * Último recurso si se pierden la contraseña, el móvil o los códigos de
 * recuperación. Borra la cuenta (los proyectos se quedan) y la web vuelve al
 * asistente de configuración inicial.
 */
final class ResetAccountCommand extends Command
{
    protected $signature = 'auth:reset {--force : No pedir confirmación}';

    protected $description = 'Borra la cuenta para volver a crearla (no toca los proyectos)';

    public function handle(ResetAccount $reset): int
    {
        if (!$this->option('force') && !$this->confirm('Se borrará la cuenta y se cerrarán todas las sesiones. ¿Seguir?')) {
            return self::FAILURE;
        }

        SetupCodeBanner::print($this, $reset->execute());

        return self::SUCCESS;
    }
}
