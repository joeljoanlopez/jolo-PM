<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Exception\RepositoryNotAccessible;
use App\Domain\Exception\SourceUnavailable;
use App\Domain\Model\FileRef;
use App\Domain\Model\PullRequest;
use App\Domain\Model\RepositoryInfo;

/**
 * Dónde viven los repos. Todos los métodos lanzan RepositoryNotAccessible si
 * el repo o la rama no existen para el token, y SourceUnavailable si el
 * servicio falla.
 *
 * @throws RepositoryNotAccessible
 * @throws SourceUnavailable
 */
interface SourceHost
{
    public function repository(string $owner, string $name): RepositoryInfo;

    /** @return list<RepositoryInfo> */
    public function accessibleRepositories(): array;

    /** @return list<FileRef> ficheros markdown de la rama */
    public function markdownFiles(string $owner, string $name, string $branch): array;

    public function blob(string $owner, string $name, string $sha): string;

    /** @return list<PullRequest> PRs abiertas cuya rama destino es $base */
    public function openPullRequests(string $owner, string $name, string $base): array;

    /** @return list<FileRef> ficheros que la PR añade o modifica, en su versión de la PR */
    public function pullRequestFiles(string $owner, string $name, int $number): array;
}
