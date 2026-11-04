<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Port\ContentCache;
use App\Infrastructure\Persistence\Models\CachedBlob;

final class EloquentContentCache implements ContentCache
{
    public function get(string $sha): ?string
    {
        $cached = CachedBlob::find($sha);
        if ($cached === null) {
            return null;
        }
        $cached->update(['last_used_at' => now()]);

        return $cached->content;
    }

    public function put(string $sha, string $content): void
    {
        CachedBlob::updateOrCreate(['sha' => $sha], ['content' => $content, 'last_used_at' => now()]);
    }

    public function prune(int $days): int
    {
        return CachedBlob::where('last_used_at', '<', now()->subDays($days))->delete();
    }
}
