<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Model\RepositoryInfo;
use App\Domain\Port\SourceHost;

final class ListRepositories
{
    public function __construct(private readonly SourceHost $source)
    {
    }

    /** @return list<RepositoryInfo> */
    public function execute(): array
    {
        $repositories = $this->source->accessibleRepositories();
        usort($repositories, static fn ($a, $b) => strcasecmp($a->fullName(), $b->fullName()));

        return $repositories;
    }
}
