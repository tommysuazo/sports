<?php

namespace App\Enums;

use App\Exceptions\KnownException;
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

    private const REGULAR_SEASON_START = '2025-09-05 05:00:00';
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
        $moment = CarbonImmutable::make($reference ?? CarbonImmutable::now('UTC'));

        if (!$moment) {
            $base = $reference ?? CarbonImmutable::now('UTC');
            $moment = CarbonImmutable::parse($base->toDateTimeString(), $base->getTimezone());
        }

        $moment = $moment->setTimezone('UTC');

        $kickoffLocal = CarbonImmutable::create(2025, 9, 4, 20, 20, 0, '-04:00');
        $firstWeekStart = $kickoffLocal->setTime(1, 0, 0)->setTimezone('UTC');

        $weekRanges = [];

        for ($weekNumber = 1; $weekNumber <= 18; $weekNumber++) {
            $start = $firstWeekStart->addWeeks($weekNumber - 1);
            $end = $start->addWeek()->subSecond();

            $weekRanges[] = [
                'week' => $weekNumber,
                'start' => $start,
                'end' => $end,
            ];
        }

        foreach ($weekRanges as $range) {
            if ($moment->betweenIncluded($range['start'], $range['end'])) {
                return self::getWeek($range['week']);
            }
        }

        return null;
    }

    public static function fromWeek(int $week): ?self
    {
        return self::tryFrom($week);
    }


    public static function getWeek(int $weekInt)
    {
        $week = match ($weekInt) {
            self::WEEK_1->value => self::WEEK_1,
            self::WEEK_2->value => self::WEEK_2,
            self::WEEK_3->value => self::WEEK_3,
            self::WEEK_4->value => self::WEEK_4,
            self::WEEK_5->value => self::WEEK_5,
            self::WEEK_6->value => self::WEEK_6,
            self::WEEK_7->value => self::WEEK_7,
            self::WEEK_8->value => self::WEEK_8,
            self::WEEK_9->value => self::WEEK_9,
            self::WEEK_10->value => self::WEEK_10,
            self::WEEK_11->value => self::WEEK_11,
            self::WEEK_12->value => self::WEEK_12,
            self::WEEK_13->value => self::WEEK_13,
            self::WEEK_14->value => self::WEEK_14,
            self::WEEK_15->value => self::WEEK_15,
            self::WEEK_16->value => self::WEEK_16,
            self::WEEK_17->value => self::WEEK_17,
            self::WEEK_18->value => self::WEEK_18,
            default => null,
        };

        if (!$week) {
            throw new KnownException("Invalid nfl week given");
        }

        return $week;
    }

    private function seasonStart(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::REGULAR_SEASON_START)->startOfDay();
    }
}
