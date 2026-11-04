<?php

declare(strict_types=1);

namespace App\Domain\Model;

enum CardStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    /**
     * Reglas de columna, en orden de prioridad. "Hecho" gana a todo: si la rama
     * principal ya lo tiene todo marcado, una PR pendiente no lo devuelve atrás.
     */
    public static function fromCounts(int $total, int $done, int $review): self
    {
        return match (true) {
            $total > 0 && $done === $total => self::Done,
            $review > 0 => self::Review,
            $done > 0 => self::InProgress,
            default => self::Todo,
        };
    }
}
