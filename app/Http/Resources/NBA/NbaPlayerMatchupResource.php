<?php

namespace App\Http\Resources\NBA;

use App\Models\NbaTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NbaPlayerMatchupResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'position' => $this->position,
            'points_market' => $this->points_market,
            'assists_market' => $this->assists_market,
            'rebounds_market' => $this->rebounds_market,
            'pra_market' => $this->pra_market,
            'steals_market' => $this->steals_market,
            'blocks_market' => $this->blocks_market,
            'scores' => $this->whenLoaded('scores'),
            'away_scores' => $this->whenLoaded('awayScores'),
            'home_scores' => $this->whenLoaded('homeScores'),
            // 'againstRivalScores' => $this->when($this->rivalTeam ?? null, $this->againstRivalScores($this->rivalTeam)->get()),
        ];
    }
}
