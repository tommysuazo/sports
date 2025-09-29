<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

enum NflWeekEnum: int
{
    case WEEK_1 = 1;
    case WEEK_2 = 2;
    case WEEK_3 = 3;
    case WEEK_4 = 4;
    case WEEK_5 = 5;
    case WEEK_6 = 6;
    case WEEK_7 = 7;
    case WEEK_8 = 8;
    case WEEK_9 = 9;
    case WEEK_10 = 10;
    case WEEK_11 = 11;
    case WEEK_12 = 12;
    case WEEK_13 = 13;
    case WEEK_14 = 14;
    case WEEK_15 = 15;
    case WEEK_16 = 16;
    case WEEK_17 = 17;
    case WEEK_18 = 18;

    private const REGULAR_SEASON_START = '2025-09-05 00:00:00';
    private const SEASON_YEAR = 2025;

    public function label(): string
    {
        return 'Semana ' . $this->value;
    }

    public function startDate(): CarbonImmutable
    {
        return $this->seasonStart()->addWeeks($this->value - 1);
    }

    public function endDate(): CarbonImmutable
    {
        return $this->startDate()->addDays(6)->endOfDay();
    }

    public function contains(CarbonInterface $date): bool
    {
        $moment = CarbonImmutable::make($date) ?? CarbonImmutable::parse($date->toDateTimeString());

        return $moment->betweenIncluded($this->startDate(), $this->endDate());
    }

    public function seasonYear(): int
    {
        return self::SEASON_YEAR;
    }

    public static function current(?CarbonInterface $reference = null): ?self
    {
        $moment = CarbonImmutable::make($reference) ?? CarbonImmutable::parse(($reference ?? now())->toDateTimeString());

        foreach (self::cases() as $week) {
            if ($week->contains($moment)) {
                return $week;
            }
        }

        return null;
    }

    public static function fromWeek(int $week): ?self
    {
        return self::tryFrom($week);
    }

    private function seasonStart(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::REGULAR_SEASON_START)->startOfDay();
    }
}
