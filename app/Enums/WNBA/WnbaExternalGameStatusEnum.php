<?php

namespace App\Enums\WNBA;

enum WnbaExternalGameStatusEnum: int
{
    case SCHEDULED = 1;
    case COMPLETED = 3;
}
