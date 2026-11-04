<?php

declare(strict_types=1);

namespace App\Domain\Model;

final class Board implements \JsonSerializable
{
    /** @param list<Card> $cards */
    public function __construct(
        public readonly string $branch,
        public readonly string $repositoryUrl,
        public readonly array $cards,
        public readonly \DateTimeImmutable $generatedAt,
    ) {
    }

    /** @return array{total: int, done: int, review: int, columns: array<string, int>} */
    public function summary(): array
    {
        $columns = array_fill_keys(array_map(static fn (CardStatus $s) => $s->value, CardStatus::cases()), 0);
        $total = $done = $review = 0;
        foreach ($this->cards as $card) {
            $columns[$card->status->value]++;
            $total += $card->total;
            $done += $card->done;
            $review += $card->review;
        }

        return ['total' => $total, 'done' => $done, 'review' => $review, 'columns' => $columns];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'branch' => $this->branch,
            'repositoryUrl' => $this->repositoryUrl,
            'generatedAt' => $this->generatedAt->format(DATE_ATOM),
            'summary' => $this->summary(),
            'cards' => $this->cards,
        ];
    }
}
