<?php

return [

    'github' => [
        // Token de solo lectura (Contents + Pull requests + Metadata). Ver README.
        'token' => env('GITHUB_TOKEN', ''),
        'api_url' => env('GITHUB_API_URL', 'https://api.github.com'),
    ],

    'sync' => [
        // Cada cuánto sincroniza el worker todos los proyectos (expresión cron).
        'cron' => env('SYNC_CRON', '*/5 * * * *'),
    ],

    'auth' => [
        // Intentos fallidos por IP (login, segundo factor, código de
        // configuración) antes de responder 429, y ventana en segundos.
        'max_attempts' => (int) env('AUTH_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('AUTH_DECAY_SECONDS', 60),
    ],

    'cache' => [
        // Días sin usarse tras los que se purga el contenido de un fichero.
        'blob_ttl_days' => (int) env('BLOB_TTL_DAYS', 30),
    ],

];
