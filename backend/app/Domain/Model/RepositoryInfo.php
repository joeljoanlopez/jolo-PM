<?php

declare(strict_types=1);

namespace App\Domain\Model;

final class RepositoryInfo
{
    public function __construct(
        public readonly string $owner,
        public readonly string $name,
        public readonly string $defaultBranch,
        public readonly bool $private,
        public readonly string $htmlUrl,
        public readonly ?string $description = null,
    ) {
    }

    public function fullName(): string
    {
        return $this->owner . '/' . $this->name;
    }
}
