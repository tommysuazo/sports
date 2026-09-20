<?php

namespace Tests\Unit;

use App\Enums\NflWeekEnum;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class NflWeekEnumTest extends TestCase
{
    public function test_current_uses_the_configured_regular_season_start(): void
    {
        $seasonStart = CarbonImmutable::parse('2026-09-09 05:00:00', 'UTC');

        $this->assertSame(NflWeekEnum::WEEK_1, NflWeekEnum::current($seasonStart));
        $this->assertTrue($seasonStart->equalTo(NflWeekEnum::WEEK_1->startDate()));
    }

    public function test_current_advances_using_seven_day_week_ranges(): void
    {
        $firstWeekEnd = CarbonImmutable::parse('2026-09-16 04:59:59', 'UTC');
        $secondWeekStart = CarbonImmutable::parse('2026-09-16 05:00:00', 'UTC');

        $this->assertSame(NflWeekEnum::WEEK_1, NflWeekEnum::current($firstWeekEnd));
        $this->assertTrue($firstWeekEnd->equalTo(NflWeekEnum::WEEK_1->endDate()));
        $this->assertSame(NflWeekEnum::WEEK_2, NflWeekEnum::current($secondWeekStart));
    }

    public function test_current_returns_null_outside_the_regular_season(): void
    {
        $beforeSeason = CarbonImmutable::parse('2026-09-09 04:59:59', 'UTC');
        $afterSeason = CarbonImmutable::parse('2027-01-13 05:00:00', 'UTC');

        $this->assertNull(NflWeekEnum::current($beforeSeason));
        $this->assertNull(NflWeekEnum::current($afterSeason));
    }
}
