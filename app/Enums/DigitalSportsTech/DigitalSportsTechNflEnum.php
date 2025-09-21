<?php

namespace App\Enums\DigitalSportsTech;

enum DigitalSportsTechNflEnum: string
{
    
    // crear modelos NflTeamMarkets & NflPlayerMarkets
    // subir todos los juegos programados
    // registrar json de mercados en otra tabla para respaldo
    
    case PASSING_YARDS = 'Passing%20Yards';
    case PASS_COMPLETIONS = 'Pass%20Completions';
    case PASS_ATTEMPTS = 'Pass%20Attempts';
    case RECEIVING_YARDS = 'Receiving%20Yards';
    case RECEPTIONS = 'Receptions';
    case RUSHING_YARDS = 'Rushing%20Yards';
    case CARRIES = 'Carries';
    case TACKLES = 'Tackles';
    case SACKS = 'Sacks';


    public static function all()
    {
        return [
            self::PASSING_YARDS->value,
            self::PASS_COMPLETIONS->value,
            self::PASS_ATTEMPTS->value,
            self::RECEIVING_YARDS->value,
            self::RECEPTIONS->value,
            self::RUSHING_YARDS->value,
            self::CARRIES->value,
            self::TACKLES->value,
            self::SACKS->value,
        ];
    }

    public static function getTeamIds()
    {
        return [
            "ARI" => 4429,
            "ATL" => 4436,
            "BAL" => 4419,
            "BUF" => 4408,
            "CAR" => 4439,
            "CHI" => 4432,
            "CIN" => 4416,
            "CLE" => 4417,
            "DAL" => 4424,
            "DEN" => 4412,
            "DET" => 4433,
            "GB"  => 4434,
            "HOU" => 4420,
            "IND" => 4422,
            "JAX" => 4423,
            "KC"  => 4413,
            "LAC" => 4415,
            "LAR" => 4428,
            "LV"  => 4414,
            "MIA" => 4409,
            "MIN" => 4435,
            "NE"  => 4410,
            "NO"  => 4437,
            "NYG" => 4425,
            "NYJ" => 4411,
            "PHI" => 4426,
            "PIT" => 4418,
            "SEA" => 4431,
            "SF"  => 4430,
            "TB"  => 4438,
            "TEN" => 4421,
            "WSH" => 4427,
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
