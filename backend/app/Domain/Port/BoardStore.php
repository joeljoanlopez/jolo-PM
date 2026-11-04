<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Model\Board;

/**
 * Último tablero calculado de cada proyecto. Un fallo de sincronización guarda
 * el error sin borrar el tablero bueno anterior.
 */
interface BoardStore
{
    public function saveBoard(int $projectId, Board $board): void;

    public function saveError(int $projectId, string $error, \DateTimeImmutable $at): void;

    /**
     * @return array{board: ?array<string, mixed>, error: ?string, attemptedAt: ?string}|null
     *         el tablero ya serializado, o null si nunca se ha sincronizado
     */
    public function load(int $projectId): ?array;
}
