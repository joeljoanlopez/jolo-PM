<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;

/** Validación de forma (presencia y tipos) con los textos de lang/{es,en}/errors.php. */
final class Input
{
    /**
     * @param array<string, list<string>> $rules
     * @return array<string, string>
     */
    public static function validate(Request $request, array $rules): array
    {
        /** @var array<string, string> $valid */
        $valid = $request->validate($rules, __('errors.request'), __('errors.attributes'));

        return $valid;
    }
}
