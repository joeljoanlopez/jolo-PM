<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Exception\DomainException;
use App\Domain\Model\Project;
use App\Domain\Port\ProjectRepository;
use App\Domain\Port\SourceHost;

final class AddProject
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly SourceHost $source,
        private readonly SyncProject $sync,
    ) {
    }

    /** @param list<string>|string $paths */
    public function execute(string $repository, ?string $branch, array|string $paths, ?string $name, ?string $groupBy = null): Project
    {
        $project = Project::fromInput(null, $repository, $branch, $paths, $name, $groupBy);

        // Antes de guardar: así un repo sin acceso da error en el formulario en
        // vez de quedar dado de alta y fallando en cada sincronización.
        $this->source->repository($project->owner, $project->name);

        $project = $this->projects->save($project);

        // El alta no falla si la primera sincronización sí: el error queda
        // guardado y se ve en el tablero.
        try {
            $this->sync->execute((int) $project->id);
        } catch (DomainException) {
        }

        return $project;
    }
}
