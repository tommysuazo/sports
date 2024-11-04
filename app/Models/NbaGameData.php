<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaGameData extends Model
{
    use HasFactory;

    protected $connection = 'sports_seeders';

    protected $table = 'nba_games';

    public $timestamps = false;

    protected $guarded = [
    ];

    protected $casts = [
        'data' => 'json',
    ];
}
