<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Model\Message;

final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    /** @throws ValidationFailed */
    public static function validate(string $password, string $confirmation): void
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            throw new ValidationFailed(['password' => new Message('validation.password_too_short', ['min' => self::MIN_LENGTH])]);
        }
        if (!hash_equals($password, $confirmation)) {
            throw new ValidationFailed(['passwordConfirmation' => new Message('validation.password_mismatch')]);
        }
    }
}
