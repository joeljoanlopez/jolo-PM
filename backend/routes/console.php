<?php

use Illuminate\Support\Facades\Schedule;

// Lo ejecuta el contenedor `worker` con `php artisan schedule:work`.

Schedule::command('projects:sync')
    ->cron(config('jolopm.sync.cron'))
    ->withoutOverlapping();

Schedule::command('blobs:prune')->daily();

Schedule::command('auth:prune-logins')->daily();
