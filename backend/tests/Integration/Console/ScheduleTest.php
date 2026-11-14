<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Tests\Integration\IntegrationTestCase;

/** Lo que el worker (`schedule:work`) lanza y cuándo: routes/console.php. */
final class ScheduleTest extends IntegrationTestCase
{
    public function test_projects_are_synced_on_the_configured_cron_without_overlapping(): void
    {
        $sync = $this->event('projects:sync');

        $this->assertSame(config('jolopm.sync.cron'), $sync->expression);
        $this->assertTrue($sync->withoutOverlapping);
    }

    public function test_the_blob_cache_is_pruned_daily(): void
    {
        $this->assertSame('0 0 * * *', $this->event('blobs:prune')->expression);
    }

    public function test_every_scheduled_command_exists(): void
    {
        $commands = array_keys($this->app->make(Kernel::class)->all());

        foreach ($this->app->make(Schedule::class)->events() as $event) {
            $line = (string) $event->command;
            $this->assertSame(1, preg_match("/artisan'? ([\w:-]+)/", $line, $m), $line);
            $this->assertContains($m[1], $commands);
        }
    }

    private function event(string $command): Event
    {
        $events = array_filter(
            $this->app->make(Schedule::class)->events(),
            fn (Event $e) => str_contains((string) $e->command, $command),
        );
        $this->assertCount(1, $events, "$command debería programarse una vez");

        return array_values($events)[0];
    }
}
