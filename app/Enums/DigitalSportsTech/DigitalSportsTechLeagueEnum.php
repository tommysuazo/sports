<?php

namespace App\Enums\DigitalSportsTech;

enum DigitalSportsTechLeagueEnum: int
{
    case NBA = 123;
    case NFL = 142;
    case NHL = 141;
    case MLB = 143;
    case WNBA = 186;

    public static function getLeagueIds(string $leagueShortName = null) 
    {
        $leagues = [
            "nba" => self::NBA->value,
            "nfl" => self::NFL->value,
            "nhl" => self::NHL->value,
            "mlb" => self::MLB->value,
            "wnba" => self::WNBA->value,
        ];

        if (is_null($leagueShortName)) {
            return $leagues;
        }

        return $leagues[$leagueShortName];
    }    
}
