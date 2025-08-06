<?php

namespace App\Enums\WNBA;

enum WnbaTeamExternalDataEnum: int
{
    // extenal teams Ids
    case ATL = 1611661330; // Atlanta Dream
    case CHI = 1611661329; // Chicago Sky
    case CON = 1611661323; // Connecticut Sun
    case DAL = 1611661321; // Dallas Wings
    case GSW = 1611661331; // Golden State Valkyries
    case IND = 1611661325; // Indiana Fever
    case LAS = 1611661320; // Los Angeles Sparks
    case LVA = 1611661319; // Las Vegas Aces
    case MIN = 1611661324; // Minnesota Lynx
    case NYL = 1611661313; // New York Liberty
    case PHO = 1611661317; // Phoenix Mercury
    case SEA = 1611661328; // Seattle Storm
    case WAS = 1611661322; // Washington Mystics

    public static function allTeamExternalIds()
    {
        return [
            'ATL' => self::ATL->value,
            'CHI' => self::CHI->value,
            'CON' => self::CON->value,
            'DAL' => self::DAL->value,
            'GSW' => self::GSW->value,
            'IND' => self::IND->value,
            'LAS' => self::LAS->value,
            'LVA' => self::LVA->value,
            'MIN' => self::MIN->value,
            'NYL' => self::NYL->value,
            'PHO' => self::PHO->value,
            'SEA' => self::SEA->value,
            'WAS' => self::WAS->value,
        ];
    }
}