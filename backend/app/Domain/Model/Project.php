<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\ValidationFailed;

/**
 * Un repo seguido por el tablero: qué ficheros mirar y en qué rama.
 * `branch` nulo significa "la rama principal del repo", que se consulta en cada
 * sincronización por si cambia.
 */
final class Project implements \JsonSerializable
{
    private const SLUG = '/^[A-Za-z0-9_.-]{1,100}$/';

    /** @param list<string> $paths */
    public function __construct(
        public readonly ?int $id,
        public readonly string $owner,
        public readonly string $name,
        public readonly ?string $branch,
        public readonly array $paths,
        public readonly ?string $displayName,
    ) {
        $errors = [];
        if (!preg_match(self::SLUG, $owner) || !preg_match(self::SLUG, $name)) {
            $errors['repository'] = 'El repositorio debe tener la forma "propietario/nombre".';
        }
        if ($branch !== null && ($branch === '' || preg_match('/[\s~^:?*\[\\\\]|\.\./', $branch))) {
            $errors['branch'] = 'Nombre de rama no válido.';
        }
        if ($paths === []) {
            $errors['paths'] = 'Indica al menos una ruta o glob, por ejemplo docs/**/*.md.';
        }
        if ($displayName !== null && mb_strlen($displayName) > 100) {
            $errors['name'] = 'El nombre no puede pasar de 100 caracteres.';
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

        return new self(
            $id,
            $owner,
            $name,
            $branch === '' ? null : $branch,
            $paths,
            $displayName === '' ? null : $displayName,
        );
    }

    public function withId(int $id): self
    {
        return new self($id, $this->owner, $this->name, $this->branch, $this->paths, $this->displayName);
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
        ];
    }
}
