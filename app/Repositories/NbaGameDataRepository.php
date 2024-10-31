<?php

namespace App\Repositories;

use App\Models\NbaGameData;
use Carbon\Carbon;

class NbaGameDataRepository 
{
    public function create(array $data): NbaGameData
    {
        return NbaGameData::create([
            'data' => $data,
            'sportsnet_id' => $data['details']['id'],
            'started_at' => Carbon::parse($data['details']['datetime'])->toDateTimeString(),
        ]);
    }
}