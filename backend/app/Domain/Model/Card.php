<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Una tarjeta del tablero: un fichero seguido o, agrupando por sección, un `##`
 * de ese fichero. `key` la identifica de forma estable (ruta, o ruta#ancla).
 */
final class Card implements \JsonSerializable
{
    /**
     * @param list<array{
     *     text: string, depth: int, line: int, section: ?string, state: TaskState, prs: list<int>
     * }> $tasks
     * @param list<PullRequest> $pullRequests PRs abiertas que marcan alguna tarea de la tarjeta
     */
    public function __construct(
        public readonly string $key,
        public readonly string $path,
        public readonly string $title,
        public readonly string $url,
        public readonly CardStatus $status,
        public readonly int $total,
        public readonly int $done,
        public readonly int $review,
        public readonly array $tasks,
        public readonly array $pullRequests,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'path' => $this->path,
            'title' => $this->title,
            'url' => $this->url,
            'status' => $this->status->value,
            'total' => $this->total,
            'done' => $this->done,
            'review' => $this->review,
            'pullRequests' => $this->pullRequests,
            'tasks' => array_map(static fn (array $t) => [
                'text' => $t['text'],
                'depth' => $t['depth'],
                'line' => $t['line'],
                'section' => $t['section'],
                'state' => $t['state']->value,
                'prs' => $t['prs'],
            ], $this->tasks),
        ];
    }
}
