<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Auth\LoginAttempt;
use App\Domain\Port\LoginLog;
use Illuminate\Database\ConnectionInterface;

/** Tabla `login_attempts`: IP, hora, paso y si salió bien. */
final class DatabaseLoginLog implements LoginLog
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function record(LoginAttempt $attempt): void
    {
        $this->db->table('login_attempts')->insert([
            'ip' => $attempt->ip,
            'method' => $attempt->method,
            'success' => $attempt->success,
            'created_at' => $attempt->at,
        ]);
    }
}
