<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaScore extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $guarded = [
        "id",
    ];

    public function team()
    {
        return $this->belongsTo(NbaTeam::class, 'team_id', 'id');
    }
}
