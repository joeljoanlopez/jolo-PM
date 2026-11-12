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

    /**
     * Inverso de jsonSerialize(). Los parámetros que son arrays se leen como
     * Message anidados.
     *
     * @param array{key: string, params?: array<string, mixed>} $data
     */
    public static function fromArray(array $data): self
    {
        $params = [];
        foreach ($data['params'] ?? [] as $name => $value) {
            /** @var array{key: string, params?: array<string, mixed>}|string|int $value */
            $params[$name] = is_array($value) ? self::fromArray($value) : $value;
        }

        return new self((string) $data['key'], $params);
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
