<?php

namespace App\Enums\Games;

enum NbaGameStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case FINAL = 'final';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getValueBySportsnetGameType(string $status)
    {
        return match ($status) {
            'Pre-Game' => self::SCHEDULED->value,
            'In-Progress' => self::IN_PROGRESS->value,
            'Final' => self::FINAL->value,
            default => throw new \Exception('Invalid game status'),
        };
    }
}

