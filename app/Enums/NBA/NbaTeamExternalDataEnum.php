<?php

namespace App\Enums\NBA;

enum NbaTeamExternalDataEnum: int
{
    // extenal teams Ids
    case BOS = 1610612738;
    case BKN = 1610612751;
    case NYK = 1610612752;
    case PHI = 1610612755;
    case TOR = 1610612761;
    case CHI = 1610612741;
    case CLE = 1610612739;
    case DET = 1610612765;
    case IND = 1610612754;
    case MIL = 1610612749;
    case ATL = 1610612737;
    case CHA = 1610612766;
    case MIA = 1610612748;
    case ORL = 1610612753;
    case WAS = 1610612764;
    case DEN = 1610612743;
    case MIN = 1610612750;
    case OKC = 1610612760;
    case POR = 1610612757;
    case UTA = 1610612762;
    case GSW = 1610612744;
    case LAC = 1610612746;
    case LAL = 1610612747;
    case PHX = 1610612756;
    case SAC = 1610612758;
    case DAL = 1610612742;
    case HOU = 1610612745;
    case MEM = 1610612763;
    case NOP = 1610612740;
    case SAS = 1610612759;

    public static function allTeamExternalIds()
    {
        return [
            'BOS' => self::BOS->value,
            'BKN' => self::BKN->value,
            'NYK' => self::NYK->value,
            'PHI' => self::PHI->value,
            'TOR' => self::TOR->value,
            'CHI' => self::CHI->value,
            'CLE' => self::CLE->value,
            'DET' => self::DET->value,
            'IND' => self::IND->value,
            'MIL' => self::MIL->value,
            'ATL' => self::ATL->value,
            'CHA' => self::CHA->value,
            'MIA' => self::MIA->value,
            'ORL' => self::ORL->value,
            'WAS' => self::WAS->value,
            'DEN' => self::DEN->value,
            'MIN' => self::MIN->value,
            'OKC' => self::OKC->value,
            'POR' => self::POR->value,
            'UTA' => self::UTA->value,
            'GSW' => self::GSW->value,
            'LAC' => self::LAC->value,
            'LAL' => self::LAL->value,
            'PHX' => self::PHX->value,
            'SAC' => self::SAC->value,
            'DAL' => self::DAL->value,
            'HOU' => self::HOU->value,
            'MEM' => self::MEM->value,
            'NOP' => self::NOP->value,
            'SAS' => self::SAS->value,
        ];
    }
}