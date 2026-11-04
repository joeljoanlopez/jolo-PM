<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Model\Message;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    public function test_round_trips_through_json_with_nested_messages(): void
    {
        $message = new Message('repository_not_accessible', [
            'resource' => new Message('resource.pull', ['number' => 7, 'repository' => 'o/r']),
        ]);

        $decoded = Message::fromArray(json_decode(json_encode($message), true));

        $this->assertEquals($message, $decoded);
    }
}
