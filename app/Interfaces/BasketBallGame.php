<?php

namespace App\Interfaces;

interface BasketBallGame
{
    public function startGame(): void;

    public function endGame(): void;

    public function addScore(string $team, int $points): void;

    public function getScore(): array;
}