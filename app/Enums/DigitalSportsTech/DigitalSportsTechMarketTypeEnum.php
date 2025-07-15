<?php

namespace App\Enums\DigitalSportsTech;

enum DigitalSportsTechMarketTypeEnum: string
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

    
}
