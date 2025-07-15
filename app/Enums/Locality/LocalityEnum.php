<?php

namespace App\Enums\Locality;

enum LocalityEnum: string
{
    case AWAY = 'away';
    case HOME = 'home';

    public static function all()
    {
        return [
            self::AWAY->value,
            self::HOME->value,
        ];
    }
}