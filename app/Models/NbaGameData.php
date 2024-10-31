<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaGameData extends Model
{
    use HasFactory;

    protected $connection = 'sports_games';

    protected $table = 'nba';

    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'data' => 'json',
    ];
}
