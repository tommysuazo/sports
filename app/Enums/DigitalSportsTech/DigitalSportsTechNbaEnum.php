<?php

namespace App\Enums\DigitalSportsTech;

enum DigitalSportsTechNbaEnum: string
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
            "ATL" => 2102, // "Atlanta Hawks" 
            "BOS" => 2092, // "Boston Celtics" 
            "BKN" => 2093, // "Brooklyn Nets" 
            "CHA" => 2103, // "Charlotte Hornets" 
            "CHI" => 2097, // "Chicago Bulls" 
            "CLE" => 2098, // "Cleveland Cavaliers" 
            "DAL" => 2112, // "Dallas Mavericks" 
            "DEN" => 2117, // "Denver Nuggets" 
            "DET" => 2099, // "Detroit Pistons" 
            "GSW" => 2107, // "Golden State Warriors" 
            "HOU" => 2113, // "Houston Rockets" 
            "IND" => 2100, // "Indiana Pacers" 
            "LAC" => 2108, // "Los Angeles Clippers" 
            "LAL" => 2109, // "Los Angeles Lakers" 
            "MEM" => 2114, // "Memphis Grizzlies" 
            "MIA" => 2104, // "Miami Heat" 
            "MIL" => 2101, // "Milwaukee Bucks" 
            "MIN" => 2118, // "Minnesota Timberwolves" 
            "NOP" => 2115, // "New Orleans Pelicans" 
            "NYK" => 2094, // "New York Knicks" 
            "OKC" => 2119, // "Oklahoma City Thunder" 
            "ORL" => 2105, // "Orlando Magic" 
            "PHI" => 2095, // "Philadelphia 76ers" 
            "PHX" => 2110, // "Phoenix Suns" 
            "POR" => 2120, // "Portland Trail Blazers" 
            "SAC" => 2111, // "Sacramento Kings" 
            "SAS" => 2116, // "San Antonio Spurs" 
            "TOR" => 2096, // "Toronto Raptors" 
            "UTA" => 2121, // "Utah Jazz" 
            "WAS" => 2106, // "Washington Wizards" 
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
