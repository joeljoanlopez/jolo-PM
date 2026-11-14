<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Exception\DomainException;
use App\Domain\Model\Board;
use App\Domain\Port\BoardStore;
use App\Domain\Port\ProjectRepository;
use App\Domain\Port\SourceHost;
use App\Domain\Service\BoardBuilder;
use App\Domain\Service\GlobMatcher;
use App\Domain\Service\MarkdownTaskParser;

/**
 * Recalcula el tablero de un proyecto a partir de GitHub y lo guarda.
 *
 * Si algo falla se guarda el error y se relanza, pero el tablero anterior se
 * conserva: es mejor ver datos de hace un rato que una pantalla vacía.
 */
final class SyncProject
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly SourceHost $source,
        private readonly BoardStore $boards,
        private readonly MarkdownTaskParser $parser,
        private readonly BoardBuilder $builder,
    ) {
    }

    public function execute(int $projectId): Board
    {
        $project = $this->projects->get($projectId);

        try {
            $repository = $this->source->repository($project->owner, $project->name);
            $branch = $project->branch ?? $repository->defaultBranch;
            $matcher = new GlobMatcher($project->paths);

            // 1. Tareas de la rama principal.
            $documents = [];
            foreach ($this->source->markdownFiles($project->owner, $project->name, $branch) as $file) {
                if (!$matcher->matches($file->path)) {
                    continue;
                }
                $content = $this->source->blob($project->owner, $project->name, $file->sha);
                $documents[$file->path] = $this->parser->parse($file->path, $content);
            }

            // 2. Versión de esos mismos ficheros en cada PR abierta que los toca.
            $versions = [];
            foreach ($this->source->openPullRequests($project->owner, $project->name, $branch) as $pr) {
                foreach ($this->source->pullRequestFiles($project->owner, $project->name, $pr->number) as $file) {
                    if (!isset($documents[$file->path])) {
                        continue;
                    }
                    $content = $this->source->blob($project->owner, $project->name, $file->sha);
                    $versions[$file->path][] = [
                        'pr' => $pr,
                        'document' => $this->parser->parse($file->path, $content),
                    ];
                }
            }

            $board = $this->builder->build(
                $repository->htmlUrl,
                $branch,
                array_values($documents),
                $versions,
                new \DateTimeImmutable(),
                $project->groupBy,
            );
        } catch (DomainException $e) {
            $this->boards->saveError($projectId, $e->reason, new \DateTimeImmutable());
            throw $e;
        }

        $this->boards->saveBoard($projectId, $board);

        return $board;
    }
}
