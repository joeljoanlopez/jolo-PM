<?php

declare(strict_types=1);

namespace App\Infrastructure\I18n;

use App\Domain\Model\Message;

/** Traduce los Message del dominio con los ficheros de lang/{es,en}/errors.php. */
final class Translator
{
    public const LOCALES = ['es', 'en'];

    public static function translate(Message $message): string
    {
        $params = array_map(
            static fn ($p) => $p instanceof Message ? self::translate($p) : (string) $p,
            $message->params,
        );

        return (string) __('errors.' . $message->key, $params);
    }

    /** Sustituye el Message de la clave `error` (si lo hay) por su texto. */
    public static function translateErrorIn(array $data): array
    {
        if (($data['error'] ?? null) instanceof Message) {
            $data['error'] = self::translate($data['error']);
        }

        return $data;
    }
}
