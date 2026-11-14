<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Exception\DuplicateProject;
use App\Domain\Exception\ProjectNotFound;
use App\Domain\Model\Project;
use App\Domain\Port\ProjectRepository;
use App\Infrastructure\Persistence\Models\ProjectRecord;

final class EloquentProjectRepository implements ProjectRepository
{
    public function all(): array
    {
        return array_values(ProjectRecord::query()
            ->orderBy('owner')
            ->orderBy('name')
            ->get()
            ->map(fn (ProjectRecord $record) => $record->toDomain())
            ->all());
    }

    public function get(int $id): Project
    {
        $record = ProjectRecord::find($id);
        if ($record === null) {
            throw new ProjectNotFound($id);
        }

        return $record->toDomain();
    }

    public function save(Project $project): Project
    {
        $duplicate = ProjectRecord::query()
            ->where('owner', $project->owner)
            ->where('name', $project->name)
            ->when($project->id !== null, fn ($q) => $q->where('id', '!=', $project->id))
            ->exists();
        if ($duplicate) {
            throw new DuplicateProject($project->fullName());
        }

        $record = $project->id === null ? new ProjectRecord() : ProjectRecord::find($project->id);
        if ($record === null) {
            throw new ProjectNotFound((int) $project->id);
        }

        $record->fill([
            'owner' => $project->owner,
            'name' => $project->name,
            'branch' => $project->branch,
            'paths' => $project->paths,
            'display_name' => $project->displayName,
            'group_by' => $project->groupBy->value,
        ])->save();

        return $record->toDomain();
    }

    public function delete(int $id): void
    {
        ProjectRecord::whereKey($id)->delete();
    }
}
