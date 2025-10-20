<?php

namespace App\Enums\NBA;

enum NbaExternalGameStatusEnum: int
{
    case SCHEDULED = 1;
    case COMPLETED = 3;
}