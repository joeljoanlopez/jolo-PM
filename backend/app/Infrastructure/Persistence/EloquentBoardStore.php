<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Model\Board;
use App\Domain\Port\BoardStore;
use App\Infrastructure\Persistence\Models\BoardSnapshot;

final class EloquentBoardStore implements BoardStore
{
    public function saveBoard(int $projectId, Board $board): void
    {
        BoardSnapshot::updateOrCreate(
            ['project_id' => $projectId],
            [
                // Ida y vuelta por JSON para guardar arrays planos, no objetos.
                'board' => json_decode(json_encode($board, JSON_THROW_ON_ERROR), true),
                'error' => null,
                'attempted_at' => $board->generatedAt,
            ],
        );
    }

    public function saveError(int $projectId, string $error, \DateTimeImmutable $at): void
    {
        // Sin tocar `board`: el último tablero bueno sigue visible.
        BoardSnapshot::updateOrCreate(
            ['project_id' => $projectId],
            ['error' => $error, 'attempted_at' => $at],
        );
    }

    public function load(int $projectId): ?array
    {
        $snapshot = BoardSnapshot::find($projectId);
        if ($snapshot === null) {
            return null;
        }

        return [
            'board' => $snapshot->board,
            'error' => $snapshot->error,
            'attemptedAt' => $snapshot->attempted_at?->format(DATE_ATOM),
        ];
    }
}
