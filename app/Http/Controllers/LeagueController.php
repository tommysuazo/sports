<?php

namespace App\Http\Controllers;

use App\Repositories\LeagueRepository;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function __construct(
        protected LeagueRepository $leagueRepository
    ) {
    }

    public function index()
    {
        return $this->leagueRepository->list();
    }
}
