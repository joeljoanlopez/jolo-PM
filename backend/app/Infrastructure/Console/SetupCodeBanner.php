<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Illuminate\Console\Command;

final class SetupCodeBanner
{
    public static function print(Command $command, string $code): void
    {
        $command->line('');
        $command->line('==============================================================');
        $command->line(' jolo-PM no tiene cuenta todavía. Abre la web y usa este código');
        $command->line(' de configuración (un solo uso; cambia en cada arranque):');
        $command->line('');
        $command->line("     $code");
        $command->line('==============================================================');
        $command->line('');
    }
}
