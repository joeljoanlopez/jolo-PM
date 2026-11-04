<?php

declare(strict_types=1);

namespace App\Domain\Model;

final class ParsedDocument
{
    /** @param list<Task> $tasks */
    public function __construct(
        public readonly string $path,
        public readonly string $title,
        public readonly array $tasks,
    ) {
    }

    /** @return array<string, Task> */
    public function tasksByKey(): array
    {
        $byKey = [];
        foreach ($this->tasks as $task) {
            $byKey[$task->key] = $task;
        }

        return $byKey;
    }
}
