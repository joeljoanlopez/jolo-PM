<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\Model\RepositoryInfo;
use App\Domain\Port\ContentCache;
use App\Domain\Port\SourceHost;

/**
 * Decorador de cualquier SourceHost: el contenido de cada fichero se pide una
 * sola vez. No sabe nada de GitHub ni de dónde se guarda la caché; los dos son
 * puertos, así que se combina con cualquier adaptador.
 */
final class CachingSourceHost implements SourceHost
{
    public function __construct(
        private readonly SourceHost $inner,
        private readonly ContentCache $cache,
    ) {
    }

    public function blob(string $owner, string $name, string $sha): string
    {
        $content = $this->cache->get($sha);
        if ($content === null) {
            $content = $this->inner->blob($owner, $name, $sha);
            $this->cache->put($sha, $content);
        }

        return $content;
    }

    public function repository(string $owner, string $name): RepositoryInfo
    {
        return $this->inner->repository($owner, $name);
    }

    public function accessibleRepositories(): array
    {
        return $this->inner->accessibleRepositories();
    }

    public function markdownFiles(string $owner, string $name, string $branch): array
    {
        return $this->inner->markdownFiles($owner, $name, $branch);
    }

    public function openPullRequests(string $owner, string $name, string $base): array
    {
        return $this->inner->openPullRequests($owner, $name, $base);
    }

    public function pullRequestFiles(string $owner, string $name, int $number): array
    {
        return $this->inner->pullRequestFiles($owner, $name, $number);
    }
}
