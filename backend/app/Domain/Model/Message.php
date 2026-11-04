<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Texto para el usuario sin traducir: una clave y sus parámetros. El dominio
 * no sabe de idiomas; la traducción la hace la capa HTTP o la consola con el
 * idioma de quien pregunta. Un parámetro puede ser otro Message (por ejemplo,
 * "el repositorio x/y" dentro de "GitHub no encuentra …").
 */
final class Message implements \JsonSerializable
{
    /** @param array<string, string|int|Message> $params */
    public function __construct(
        public readonly string $key,
        public readonly array $params = [],
    ) {
    }

    /** @param array{key: string, params?: array<string, mixed>} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['key'],
            array_map(
                static fn ($p) => is_array($p) ? self::fromArray($p) : $p,
                $data['params'] ?? [],
            ),
        );
    }

    /** @return array{key: string, params: array<string, mixed>} */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'params' => array_map(
                static fn ($p) => $p instanceof self ? $p->jsonSerialize() : $p,
                $this->params,
            ),
        ];
    }
}
