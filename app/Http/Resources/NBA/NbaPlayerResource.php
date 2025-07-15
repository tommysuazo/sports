<?php

namespace App\Http\Resources\NBA;

use App\Http\Resources\NbaPlayerScoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NbaPlayerResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'position' => $this->position,
            'team' => $this->whenLoaded('team', fn() => new NbaTeamResource($this->team)),
            'scores' => $this->whenLoaded(
                'scores',
                fn() => NbaPlayerScoreResource::collection($this->scores)
            ),
            'away_scores' => $this->whenLoaded(
                'awayScores',
                fn() => NbaPlayerScoreResource::collection($this->awayScores)
            ),
            'home_scores' => $this->whenLoaded(
                'homeScores',
                fn() => NbaPlayerScoreResource::collection($this->homeScores)
            ),
            'against_scores' => $this->when(
                $this->againstScores,
                fn() => NbaPlayerScoreResource::collection($this->againstScores)
            ),
        ];
    }
}
