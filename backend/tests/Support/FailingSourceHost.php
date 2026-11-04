<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Exception\SourceUnavailable;
use App\Domain\Model\RepositoryInfo;
use App\Domain\Port\SourceHost;

/** Simula GitHub caído: el repo existe pero cualquier lectura falla. */
class FailingSourceHost implements SourceHost
{
    public function __construct(private readonly SourceHost $inner)
    {
    }

    public function repository(string $owner, string $name): RepositoryInfo
    {
        return $this->inner->repository($owner, $name);
    }

    public function accessibleRepositories(): array
    {
        throw SourceUnavailable::connectionFailed('timeout');
    }

    public function markdownFiles(string $owner, string $name, string $branch): array
    {
        throw SourceUnavailable::connectionFailed('timeout');
    }

    public function blob(string $owner, string $name, string $sha): string
    {
        throw SourceUnavailable::connectionFailed('timeout');
    }

    public function openPullRequests(string $owner, string $name, string $base): array
    {
        throw SourceUnavailable::connectionFailed('timeout');
    }

    public function pullRequestFiles(string $owner, string $name, int $number): array
    {
        throw SourceUnavailable::connectionFailed('timeout');
    }
}
