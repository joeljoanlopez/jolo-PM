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

    'cache' => [
        // Días sin usarse tras los que se purga el contenido de un fichero.
        'blob_ttl_days' => (int) env('BLOB_TTL_DAYS', 30),
    ],

    'auth' => [
        // Intentos fallidos por IP y minuto en login, segundo factor y
        // configuración inicial, antes de responder 429.
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => 60,
        // Días que se guarda el registro de inicios de sesión.
        'login_log_days' => (int) env('LOGIN_LOG_DAYS', 90),
    ],

];
