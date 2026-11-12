<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Model\Board;
use App\Domain\Model\Card;
use App\Domain\Model\CardStatus;
use App\Domain\Model\GroupBy;
use App\Domain\Model\ParsedDocument;
use App\Domain\Model\PullRequest;
use App\Domain\Model\TaskState;

/**
 * Convierte los documentos leídos (rama principal + versiones de las PRs
 * abiertas) en el tablero. Aquí viven todas las reglas de estado; el resto de
 * la app solo mueve datos.
 */
final class BoardBuilder
{
    /**
     * @param list<ParsedDocument> $documents versión de la rama principal
     * @param array<string, list<array{pr: PullRequest, document: ParsedDocument}>> $pullRequestVersions
     *        por ruta, la versión de ese fichero en cada PR abierta que lo toca
     */
    public function build(
        string $repositoryUrl,
        string $branch,
        array $documents,
        array $pullRequestVersions,
        \DateTimeImmutable $now,
        GroupBy $groupBy = GroupBy::File,
    ): Board {
        $cards = [];
        foreach ($documents as $document) {
            $fileUrl = $repositoryUrl . '/blob/' . rawurlencode($branch) . '/' . self::encodePath($document->path);
            foreach ($this->groups($document, $groupBy) as $group) {
                $cards[] = $this->card(
                    $group['key'],
                    $group['document'],
                    $pullRequestVersions[$document->path] ?? [],
                    $fileUrl . ($group['anchor'] !== null ? '#' . $group['anchor'] : ''),
                );
            }
        }

        // usort es estable: las secciones de un fichero quedan en su orden.
        usort($cards, static fn (Card $a, Card $b) => strnatcasecmp($a->path, $b->path));

        return new Board($branch, $repositoryUrl, $cards, $now);
    }

    /**
     * Reparte las tareas del documento en tarjetas. Por sección, cada `##` con
     * casillas es una tarjeta titulada `título del fichero/sección` (clave
     * fichero#ancla) y las casillas anteriores al primer `##` van a una con el
     * título del fichero (clave = ruta).
     *
     * @return list<array{key: string, anchor: ?string, document: ParsedDocument}>
     */
    private function groups(ParsedDocument $document, GroupBy $groupBy): array
    {
        if ($document->tasks === []) {
            return [];
        }
        if ($groupBy === GroupBy::File) {
            return [['key' => $document->path, 'anchor' => null, 'document' => $document]];
        }

        $groups = [];
        foreach ($document->tasks as $task) {
            $anchor = $task->chapterAnchor;
            $groups[$anchor ?? ''] ??= [
                'anchor' => $anchor,
                'title' => $task->chapter !== null ? $document->title . '/' . $task->chapter : $document->title,
                'tasks' => [],
            ];
            $groups[$anchor ?? '']['tasks'][] = $task;
        }

        return array_values(array_map(static fn (array $g) => [
            'key' => $document->path . ($g['anchor'] !== null ? '#' . $g['anchor'] : ''),
            'anchor' => $g['anchor'],
            'document' => new ParsedDocument($document->path, $g['title'], $g['tasks']),
        ], $groups));
    }

    /** @param list<array{pr: PullRequest, document: ParsedDocument}> $versions */
    private function card(string $key, ParsedDocument $document, array $versions, string $url): Card
    {
        // Para cada PR, qué claves de tarea quedan marcadas en su versión.
        $checkedIn = [];
        foreach ($versions as $version) {
            foreach ($version['document']->tasks as $task) {
                if ($task->checked) {
                    $checkedIn[$version['pr']->number][$task->key] = true;
                }
            }
        }

        $tasks = [];
        $done = $review = 0;
        $involvedPrs = [];

        foreach ($document->tasks as $task) {
            $prs = [];
            if (!$task->checked) {
                foreach ($versions as $version) {
                    $number = $version['pr']->number;
                    if (isset($checkedIn[$number][$task->key])) {
                        $prs[] = $number;
                        $involvedPrs[$number] = $version['pr'];
                    }
                }
            }

            $state = match (true) {
                $task->checked => TaskState::Done,
                $prs !== [] => TaskState::Review,
                default => TaskState::Pending,
            };
            $done += $state === TaskState::Done ? 1 : 0;
            $review += $state === TaskState::Review ? 1 : 0;

            $tasks[] = [
                'text' => $task->text,
                'depth' => $task->depth,
                'line' => $task->line,
                'section' => $task->section,
                'state' => $state,
                'prs' => $prs,
            ];
        }

        ksort($involvedPrs);
        $total = count($tasks);

        return new Card(
            key: $key,
            path: $document->path,
            title: $document->title,
            url: $url,
            status: CardStatus::fromCounts($total, $done, $review),
            total: $total,
            done: $done,
            review: $review,
            tasks: $tasks,
            pullRequests: array_values($involvedPrs),
        );
    }

    private static function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
