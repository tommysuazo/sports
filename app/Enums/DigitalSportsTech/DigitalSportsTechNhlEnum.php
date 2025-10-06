<?php

namespace App\Enums\DigitalSportsTech;

enum DigitalSportsTechNhlEnum: string
{
    // Valores temporales; ajustar cuando DST confirme los mercados definitivos.
    case GOALS = 'Goals';
    case ASSISTS = 'Assists';
    case POINTS = 'Points';
    case SHOTS = 'Shots';
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
            "ANA" => 9601,
            "BOS" => 9602,
            "BUF" => 9603,
            "CAR" => 9604,
            "CBJ" => 9605,
            "CGY" => 9606,
            "CHI" => 9607,
            "COL" => 9608,
            "DAL" => 9609,
            "DET" => 9610,
            "EDM" => 9611,
            "FLA" => 9612,
            "LAK" => 9613,
            "MIN" => 9614,
            "MTL" => 9615,
            "NJD" => 9616,
            "NSH" => 9617,
            "NYI" => 9618,
            "NYR" => 9619,
            "OTT" => 9620,
            "PHI" => 9621,
            "PIT" => 9622,
            "SEA" => 9623,
            "SJS" => 9624,
            "STL" => 9625,
            "TBL" => 9626,
            "TOR" => 9627,
            "UTA" => 9628,
            "VAN" => 9629,
            "VGK" => 9630,
            "WPG" => 9631,
            "WSH" => 9632,
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
