<?php

declare(strict_types=1);

namespace App\Domain\Model;

final class PullRequest implements \JsonSerializable
{
    public function __construct(
        public readonly int $number,
        public readonly string $title,
        public readonly string $url,
        public readonly bool $draft,
        public readonly string $headSha,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'number' => $this->number,
            'title' => $this->title,
            'url' => $this->url,
            'draft' => $this->draft,
        ];
    }
}
