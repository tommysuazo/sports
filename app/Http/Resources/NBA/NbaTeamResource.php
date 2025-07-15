<?php

namespace App\Http\Resources\NBA;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NbaTeamResource extends JsonResource
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
            'name' => $this->name,
            'city' => $this->city,
            'players' => $this->whenLoaded('players', fn() => NbaPlayerResource::collection($this->players)),
        ];
    }
}
