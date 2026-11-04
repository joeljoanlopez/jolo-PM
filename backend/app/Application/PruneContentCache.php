<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Port\ContentCache;

final class PruneContentCache
{
    public function __construct(private readonly ContentCache $cache)
    {
    }

    public function execute(int $days): int
    {
        return $this->cache->prune($days);
    }
}
