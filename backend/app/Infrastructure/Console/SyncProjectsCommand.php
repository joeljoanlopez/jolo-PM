<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\SyncAllProjects;
use App\Application\SyncProject;
use App\Domain\Exception\DomainException;
use App\Infrastructure\I18n\Translator;
use Illuminate\Console\Command;

/** Adaptador de entrada por consola. El scheduler lo lanza cada SYNC_CRON. */
final class SyncProjectsCommand extends Command
{
    protected $signature = 'projects:sync {id? : Solo este proyecto}';

    protected $description = 'Sincroniza los tableros con GitHub';

    public function handle(SyncAllProjects $syncAll, SyncProject $syncOne): int
    {
        $id = $this->argument('id');
        if ($id !== null) {
            try {
                $board = $syncOne->execute((int) $id);
            } catch (DomainException $e) {
                $this->error(Translator::translate($e->reason));

                return self::FAILURE;
            }
            $this->info(sprintf('Proyecto %d: %d tarjetas.', $id, count($board->cards)));

            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($syncAll->execute() as $repository => $error) {
            if ($error === null) {
                $this->line("✓ $repository");
            } else {
                $failed++;
                $this->error("✗ $repository: " . Translator::translate($error));
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
