<?php

namespace App\Http\Resources\NBA;

use App\Http\Resources\NbaPlayerStatResource;
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
            'team' => $this->whenLoaded('team', fn () => new NbaTeamResource($this->team)),
            'stats' => $this->whenLoaded(
                'stats',
                fn () => NbaPlayerStatResource::collection($this->stats)
            ),
            'scores' => $this->when(
                $this->relationLoaded('scores') || $this->relationLoaded('stats'),
                fn () => NbaPlayerStatResource::collection(
                    $this->relationLoaded('scores') ? $this->scores : $this->stats
                )
            ),
            'away_stats' => $this->whenLoaded(
                'awayStats',
                fn () => NbaPlayerStatResource::collection($this->awayStats)
            ),
            'away_scores' => $this->when(
                $this->relationLoaded('awayScores') || $this->relationLoaded('awayStats'),
                fn () => NbaPlayerStatResource::collection(
                    $this->relationLoaded('awayScores') ? $this->awayScores : $this->awayStats
                )
            ),
            'home_stats' => $this->whenLoaded(
                'homeStats',
                fn () => NbaPlayerStatResource::collection($this->homeStats)
            ),
            'home_scores' => $this->when(
                $this->relationLoaded('homeScores') || $this->relationLoaded('homeStats'),
                fn () => NbaPlayerStatResource::collection(
                    $this->relationLoaded('homeScores') ? $this->homeScores : $this->homeStats
                )
            ),
            'against_scores' => $this->when(
                $this->againstScores,
                fn () => NbaPlayerStatResource::collection($this->againstScores)
            ),
        ];
    }
}
