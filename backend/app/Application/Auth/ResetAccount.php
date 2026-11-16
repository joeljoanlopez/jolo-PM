<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Port\AccountRepository;

/**
 * Recuperar el acceso: borra la cuenta (no los proyectos) y emite un código
 * de configuración nuevo. Todas las sesiones dejan de valer, porque estaban
 * atadas al id de la cuenta borrada.
 */
final class ResetAccount
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly IssueSetupCode $issue,
    ) {
    }

    /** @return string el código de configuración nuevo */
    public function execute(): string
    {
        $this->accounts->delete();

        return (string) $this->issue->execute();
    }
}
