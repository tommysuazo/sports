<?php

namespace App\Enums\DigitalSportsTech;

enum DigitalSportsTechWnbaEnum: string
{
    case POINTS = 'Points';
    case ASSISTS = 'Assists';
    case REBOUNDS = 'Total%20Rebounds';
    case PRA = 'Pts%20+%20Reb%20+%20Ast';
    case TRIPLES = 'Three%20Point%20Field%20Goals%20Made';

    public static function all(string $type = null)
    {
        $marketTypes = [
            'points' => self::POINTS->value,
            'assists' => self::ASSISTS->value,
            'rebounds' => self::REBOUNDS->value,
            'pra' => self::PRA->value,
            'pt3' => self::TRIPLES->value,
        ];

        return $marketTypes[$type] ?? $marketTypes;
    }

    

    public static function getTeamIds()
    {
        return [
            "ATL" => 9989, // Atlanta Dream
            "CHI" => 9980, // Chicago Sky
            "CON" => 9987, // Connecticut Sun
            "DAL" => 9988, // Dallas Wings
            "GSW" => 10677, // Golden State Valkyries
            "IND" => 9979, // Indiana Fever
            "LAS" => 9981, // Los Angeles Sparks
            "LVA" => 9985, // Las Vegas Aces
            "MIN" => 9983, // Minnesota Lynx
            "NYL" => 9986, // New York Liberty
            "PHO" => 9984, // Phoenix Mercury
            "SEA" => 9982, // Seattle Storm
            "WAS" => 9978, // Washington Mystics
        ];

    }

    
    public static function getTeamId(string $teamShortName = null)
    {
        $teams = self::getTeamIds();
        
        if (is_null($teamShortName)) {
            return $teams;
        }

        return $teams[$teamShortName];
    }
}
