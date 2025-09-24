<?php

namespace Tests\Unit;

use App\Models\NflPlayer;
use PHPUnit\Framework\TestCase;

class NflPlayerTest extends TestCase
{
    public function test_full_name_accessor_combines_first_and_last_name(): void
    {
        $player = new NflPlayer([
            'first_name' => 'Patrick',
            'last_name' => 'Mahomes',
        ]);

        $this->assertSame('Patrick Mahomes', $player->full_name);
    }

    public function test_full_name_accessor_omits_empty_segments(): void
    {
        $player = new NflPlayer([
            'first_name' => 'Madonna',
            'last_name' => null,
        ]);

        $this->assertSame('Madonna', $player->full_name);
    }

    public function test_full_name_is_appended_in_serialized_output(): void
    {
        $player = new NflPlayer([
            'first_name' => 'Josh',
            'last_name' => 'Allen',
        ]);

        $array = $player->toArray();

        $this->assertArrayHasKey('full_name', $array);
        $this->assertSame('Josh Allen', $array['full_name']);
    }
}
