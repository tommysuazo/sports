<?php

namespace App\Http\Resources\NBA;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NbaGameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'started_at' => $this->started_at,
            'away_team' => new NbaTeamResource($this->awayTeam),
            'home_team' => new NbaTeamResource($this->homeTeam),
        ];
    }
}
