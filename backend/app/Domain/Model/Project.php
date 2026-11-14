<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\ValidationFailed;

/**
 * Un repo seguido por el tablero: qué ficheros mirar y en qué rama.
 * `branch` nulo significa "la rama principal del repo", que se consulta en cada
 * sincronización por si cambia. `groupBy` dice si cada fichero es una tarjeta
 * o lo es cada una de sus secciones `##`.
 */
final class Project implements \JsonSerializable
{
    private const SLUG = '/^[A-Za-z0-9_.-]{1,100}$/';

    public readonly GroupBy $groupBy;

    /** @param list<string> $paths */
    public function __construct(
        public readonly ?int $id,
        public readonly string $owner,
        public readonly string $name,
        public readonly ?string $branch,
        public readonly array $paths,
        public readonly ?string $displayName,
        GroupBy|string $groupBy = GroupBy::File,
    ) {
        $errors = [];
        $this->groupBy = is_string($groupBy) ? (GroupBy::tryFrom($groupBy) ?? GroupBy::File) : $groupBy;
        if (is_string($groupBy) && GroupBy::tryFrom($groupBy) === null) {
            $errors['groupBy'] = new Message('validation.group_by_invalid');
        }
        if (!preg_match(self::SLUG, $owner) || !preg_match(self::SLUG, $name)) {
            $errors['repository'] = new Message('validation.repository_format');
        }
        if ($branch !== null && ($branch === '' || preg_match('/[\s~^:?*\[\\\\]|\.\./', $branch))) {
            $errors['branch'] = new Message('validation.branch_invalid');
        }
        if ($paths === []) {
            $errors['paths'] = new Message('validation.paths_required');
        }
        if ($displayName !== null && mb_strlen($displayName) > 100) {
            $errors['name'] = new Message('validation.name_too_long');
        }
        if ($errors !== []) {
            throw new ValidationFailed($errors);
        }
    }

    /**
     * Normaliza la entrada de usuario: recorta, quita líneas vacías y
     * duplicados, y trata cadenas vacías como "sin valor".
     *
     * @param list<string>|string $paths
     */
    public static function fromInput(
        ?int $id,
        string $repository,
        ?string $branch,
        array|string $paths,
        ?string $displayName,
        ?string $groupBy = null,
    ): self {
        $repository = trim($repository);
        $repository = (string) preg_replace('#^(https?://github\.com/|git@github\.com:)#', '', $repository);
        $repository = (string) preg_replace('#(\.git)?/?$#', '', $repository);
        [$owner, $name] = array_pad(explode('/', $repository, 2), 2, '');

        if (is_string($paths)) {
            $paths = preg_split('/\r\n|\r|\n/', $paths) ?: [];
        }
        $paths = array_values(array_unique(array_filter(
            array_map(static fn ($p) => ltrim(trim((string) $p), '/'), $paths),
            static fn (string $p) => $p !== '',
        )));

        $branch = $branch !== null ? trim($branch) : null;
        $displayName = $displayName !== null ? trim($displayName) : null;
        $groupBy = $groupBy !== null ? trim($groupBy) : null;

        return new self(
            $id,
            $owner,
            $name,
            $branch === '' ? null : $branch,
            $paths,
            $displayName === '' ? null : $displayName,
            $groupBy === null || $groupBy === '' ? GroupBy::File : $groupBy,
        );
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->owner,
            $this->name,
            $this->branch,
            $this->paths,
            $this->displayName,
            $this->groupBy,
        );
    }

    public function fullName(): string
    {
        return $this->owner . '/' . $this->name;
    }

    public function label(): string
    {
        return $this->displayName ?? $this->name;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'repository' => $this->fullName(),
            'branch' => $this->branch,
            'paths' => $this->paths,
            'name' => $this->displayName,
            'label' => $this->label(),
            'groupBy' => $this->groupBy->value,
        ];
    }
}
