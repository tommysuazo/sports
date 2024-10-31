<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaTeam extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [
        "id",
    ];

    public function players()
    {
        return $this->hasMany(NbaPlayer::class, 'team_id');
    }
}
