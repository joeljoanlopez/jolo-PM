<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use App\Domain\Port\ContentCache;
use Tests\Integration\IntegrationTestCase;

final class PruneBlobCacheCommandTest extends IntegrationTestCase
{
    public function test_prunes_what_has_not_been_used_within_the_configured_days(): void
    {
        config(['jolopm.cache.blob_ttl_days' => 10]);
        $cache = $this->app->make(ContentCache::class);
        $cache->put('viejo', 'a');
        $this->travel(11)->days();
        $cache->put('nuevo', 'b');

        $this->artisan('blobs:prune')
            ->expectsOutput('1 ficheros purgados.')
            ->assertSuccessful();

        $this->assertNull($cache->get('viejo'));
        $this->assertSame('b', $cache->get('nuevo'));
    }
}
