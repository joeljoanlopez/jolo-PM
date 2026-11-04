<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Model\Board;
use App\Domain\Model\Message;
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

    public function saveError(int $projectId, Message $error, \DateTimeImmutable $at): void
    {
        // Sin tocar `board`: el último tablero bueno sigue visible.
        BoardSnapshot::updateOrCreate(
            ['project_id' => $projectId],
            ['error' => json_encode($error, JSON_THROW_ON_ERROR), 'attempted_at' => $at],
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
            'error' => self::decodeError($snapshot->error),
            'attemptedAt' => $snapshot->attempted_at?->format(DATE_ATOM),
        ];
    }

    /**
     * El error se guarda como Message en JSON para traducirlo al leerlo. Los
     * guardados antes eran texto ya en castellano: se devuelven tal cual.
     */
    private static function decodeError(?string $raw): ?Message
    {
        if ($raw === null) {
            return null;
        }
        $data = json_decode($raw, true);

        return is_array($data) && isset($data['key'])
            ? Message::fromArray($data)
            : new Message('legacy', ['text' => $raw]);
    }
}
