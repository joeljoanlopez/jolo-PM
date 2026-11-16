<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Auth;

use App\Domain\Service\Auth\Base32;
use PHPUnit\Framework\TestCase;

final class Base32Test extends TestCase
{
    public function test_matches_the_rfc_4648_vectors_without_padding(): void
    {
        $vectors = [
            '' => '',
            'f' => 'MY',
            'fo' => 'MZXQ',
            'foo' => 'MZXW6',
            'foob' => 'MZXW6YQ',
            'foobar' => 'MZXW6YTBOI',
        ];

        foreach ($vectors as $plain => $encoded) {
            $this->assertSame($encoded, Base32::encode($plain));
            $this->assertSame($plain, Base32::decode($encoded));
        }
    }

    public function test_decoding_accepts_lowercase_spaces_and_padding(): void
    {
        $this->assertSame('foobar', Base32::decode('mzxw 6ytb oi======'));
    }

    public function test_rejects_characters_outside_the_alphabet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Base32::decode('MZXW1');
    }
}
