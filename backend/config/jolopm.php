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

];
