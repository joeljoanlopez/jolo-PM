<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Port\ProjectRepository;

/** Deja de seguir un repo. No toca nada en GitHub. */
final class RemoveProject
{
    public function __construct(private readonly ProjectRepository $projects)
    {
    }

    public function execute(int $id): void
    {
        $this->projects->get($id);
        $this->projects->delete($id);
    }
}
