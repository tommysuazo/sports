<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NcaabTeamScore extends BasketballTeamScore
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(NcaabTeam::class, 'team_id', 'id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(NcaabGame::class, 'game_id', 'id');
    }
}
