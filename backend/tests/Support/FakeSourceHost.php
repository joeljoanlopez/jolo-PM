<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Exception\RepositoryNotAccessible;
use App\Domain\Model\Message;
use App\Domain\Model\FileRef;
use App\Domain\Model\PullRequest;
use App\Domain\Model\RepositoryInfo;
use App\Domain\Port\SourceHost;

/** SourceHost en memoria: un único repo `o/r` con rama `main`. */
final class FakeSourceHost implements SourceHost
{
    /** @var array<string, string> ruta => markdown en main */
    public array $files = [];

    /** @var array<int, array{pr: PullRequest, files: array<string, string>}> */
    public array $pullRequests = [];

    /** @var list<string> */
    public array $blobRequests = [];

    public function repository(string $owner, string $name): RepositoryInfo
    {
        if ("$owner/$name" !== 'o/r') {
            throw new RepositoryNotAccessible(new Message('resource.repository', ['repository' => "$owner/$name"]));
        }

        return new RepositoryInfo('o', 'r', 'main', true, 'https://github.com/o/r');
    }

    public function accessibleRepositories(): array
    {
        return [$this->repository('o', 'r')];
    }

    public function markdownFiles(string $owner, string $name, string $branch): array
    {
        return array_map(fn ($path) => new FileRef($path, sha1($this->files[$path])), array_keys($this->files));
    }

    public function blob(string $owner, string $name, string $sha): string
    {
        $this->blobRequests[] = $sha;
        foreach ([$this->files, ...array_column($this->pullRequests, 'files')] as $set) {
            foreach ($set as $content) {
                if (sha1($content) === $sha) {
                    return $content;
                }
            }
        }
        throw new RepositoryNotAccessible(new Message('resource.blob', ['sha' => $sha, 'repository' => "$owner/$name"]));
    }

    public function openPullRequests(string $owner, string $name, string $base): array
    {
        return array_column($this->pullRequests, 'pr');
    }

    public function pullRequestFiles(string $owner, string $name, int $number): array
    {
        $files = $this->pullRequests[$number]['files'] ?? [];

        return array_map(fn ($path) => new FileRef($path, sha1($files[$path])), array_keys($files));
    }

    /** @param array<string, string> $files */
    public function openPullRequest(int $number, array $files): void
    {
        $this->pullRequests[$number] = [
            'pr' => new PullRequest($number, "PR $number", "https://github.com/o/r/pull/$number", false, 'sha'),
            'files' => $files,
        ];
    }
}
