<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\PruneContentCache;
use Illuminate\Console\Command;

final class PruneBlobCacheCommand extends Command
{
    protected $signature = 'blobs:prune';

    protected $description = 'Borra de la caché los ficheros que ya no se usan';

    public function handle(PruneContentCache $prune): int
    {
        $deleted = $prune->execute((int) config('jolopm.cache.blob_ttl_days'));
        $this->info("$deleted ficheros purgados.");

        return self::SUCCESS;
    }
}
