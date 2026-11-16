<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Model\Auth\LoginAttempt;
use App\Domain\Port\LoginLog;
use App\Infrastructure\Persistence\Models\LoginAttemptRecord;

final class EloquentLoginLog implements LoginLog
{
    public function record(LoginAttempt $attempt): void
    {
        LoginAttemptRecord::query()->create([
            'ip' => $attempt->ip,
            'succeeded' => $attempt->succeeded,
            'step' => $attempt->step->value,
            'created_at' => $attempt->at,
        ]);
    }

    public function purgeBefore(\DateTimeImmutable $limit): int
    {
        return (int) LoginAttemptRecord::query()->where('created_at', '<', $limit)->delete();
    }
}
