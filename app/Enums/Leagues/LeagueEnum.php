<?php

namespace App\Enums\Leagues;

enum LeagueEnum: string
{
    case NBA = 'nba';
    case NFL = 'nfl';
    case NHL = 'nhl';
    case NCAAB = 'ncaab';

    public static function get()
    {
        return [
            self::NBA->value,
            self::NFL->value,
            self::NHL->value,
            self::NCAAB->value,
        ];
    }

    public static function getSupportedBySportsnet()
    {
        return [
            self::NBA->value,
            self::NFL->value,
            self::NHL->value,
        ];
    }

    public static function getFullNames()
    {
        return [
            self::NBA->value => 'National Basketball Association',
            self::NFL->value => 'National Football League',
            self::NHL->value => 'National Hockey League',
            self::NCAAB->value => 'NCAA Basketball',
        ];
    }

    public static function getFullName(string $code = null)
    {
        $leagueNames = self::getFullNames();

        return $leagueNames[$code];
    }
}