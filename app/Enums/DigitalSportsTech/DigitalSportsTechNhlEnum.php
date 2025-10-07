<?php

namespace App\Enums\DigitalSportsTech;

enum DigitalSportsTechNhlEnum: string
{
    // Valores temporales; ajustar cuando DST confirme los mercados definitivos.
    case GOALS = 'Goals';
    case ASSISTS = 'Assists';
    case POINTS = 'Points';
    case SHOTS = 'Shots%2520on%2520goal';
    case SAVES = 'Saves';

    public static function all()
    {
        return [
            self::GOALS->value,
            self::ASSISTS->value,
            self::POINTS->value,
            self::SHOTS->value,
            self::SAVES->value,
        ];
    }

    public static function getTeamIds()
    {
        return [
            "ANA" => 6154,
            "BOS" => 6150,
            "BUF" => 6156,
            "CAR" => 6157,
            "CBJ" => 6160,
            "CGY" => 6152,
            "CHI" => 6164,
            "COL" => 6169,
            "DAL" => 6167,
            "DET" => 6159,
            "EDM" => 6175,
            "FLA" => 6177,
            "LAK" => 6173,
            "MIN" => 6170,
            "MTL" => 6148,
            "NJD" => 6174,
            "NSH" => 6162,
            "NYI" => 6158,
            "NYR" => 6161,
            "OTT" => 6163,
            "PHI" => 6172,
            "PIT" => 6155,
            "SEA" => 6654,
            "SJS" => 6153,
            "STL" => 6165,
            "TBL" => 6176,
            "TOR" => 6147,
            "UTA" => 10189,
            "VAN" => 6151,
            "VGK" => 6171,
            "WPG" => 6166,
            "WSH" => 6149,
        ];
    }

    public static function getTeamId(string $teamShortName = null)
    {
        $teams = self::getTeamIds();

        if (is_null($teamShortName)) {
            return $teams;
        }

        return $teams[strtoupper($teamShortName)] ?? null;
    }
}
