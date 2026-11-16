<?php

declare(strict_types=1);

namespace App\Domain\Model\Auth;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Model\Message;

/** Reglas de la contraseña: mínimo 12 caracteres y escrita igual dos veces. */
final class Password
{
    public const MIN_LENGTH = 12;

    /** @throws ValidationFailed */
    public static function validate(string $password, string $confirmation): void
    {
        $errors = [];
        if (mb_strlen($password) < self::MIN_LENGTH) {
            $errors['password'] = new Message('validation.password_too_short', ['min' => self::MIN_LENGTH]);
        } elseif (!hash_equals($password, $confirmation)) {
            $errors['passwordConfirmation'] = new Message('validation.password_mismatch');
        }
        if ($errors !== []) {
            throw new ValidationFailed($errors);
        }
    }
}
