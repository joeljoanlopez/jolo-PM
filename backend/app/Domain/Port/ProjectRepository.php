<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Exception\DuplicateProject;
use App\Domain\Exception\ProjectNotFound;
use App\Domain\Model\Project;

interface ProjectRepository
{
    /** @return list<Project> */
    public function all(): array;

    /** @throws ProjectNotFound */
    public function get(int $id): Project;

    /**
     * Inserta si no tiene id; si lo tiene, actualiza.
     *
     * @throws DuplicateProject
     */
    public function save(Project $project): Project;

    public function delete(int $id): void;
}
