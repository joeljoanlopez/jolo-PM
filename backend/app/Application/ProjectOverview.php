<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Model\Project;
use App\Domain\Port\BoardStore;
use App\Domain\Port\ProjectRepository;

/**
 * Lecturas para la interfaz: listado de proyectos con su resumen y el tablero
 * de uno. Solo leen lo ya sincronizado; nunca llaman a GitHub.
 */
final class ProjectOverview
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly BoardStore $boards,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return array_map(function (Project $project) {
            $stored = $this->boards->load((int) $project->id);

            return [
                ...$project->jsonSerialize(),
                'summary' => $stored['board']['summary'] ?? null,
                'syncedAt' => $stored['board']['generatedAt'] ?? null,
                'error' => $stored['error'] ?? null,
            ];
        }, $this->projects->all());
    }

    /** @return array<string, mixed> */
    public function board(int $id): array
    {
        $project = $this->projects->get($id);
        $stored = $this->boards->load($id);

        return [
            'project' => $project,
            'board' => $stored['board'] ?? null,
            'error' => $stored['error'] ?? null,
            'attemptedAt' => $stored['attemptedAt'] ?? null,
        ];
    }
}
