<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Exception\DomainException;
use App\Domain\Model\Project;
use App\Domain\Port\ProjectRepository;

/** Cambia rama, rutas o nombre. El repositorio no se cambia: eso es otro proyecto. */
final class UpdateProject
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly SyncProject $sync,
    ) {
    }

    /** @param list<string>|string $paths */
    public function execute(int $id, ?string $branch, array|string $paths, ?string $name): Project
    {
        $current = $this->projects->get($id);
        $project = $this->projects->save(
            Project::fromInput($id, $current->fullName(), $branch, $paths, $name),
        );

        try {
            $this->sync->execute($id);
        } catch (DomainException) {
        }

        return $project;
    }
}
