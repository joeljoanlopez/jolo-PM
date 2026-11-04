<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Exception\DomainException;
use App\Domain\Port\ProjectRepository;

final class SyncAllProjects
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly SyncProject $sync,
    ) {
    }

    /**
     * Un proyecto que falla no impide sincronizar los demás.
     *
     * @return array<string, ?string> repositorio => null si fue bien, o el error
     */
    public function execute(): array
    {
        $results = [];
        foreach ($this->projects->all() as $project) {
            try {
                $this->sync->execute((int) $project->id);
                $results[$project->fullName()] = null;
            } catch (DomainException $e) {
                $results[$project->fullName()] = $e->getMessage();
            }
        }

        return $results;
    }
}
