<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

abstract class MultiLeagueRepository
{
    /**
     * Liga activa actual (ej: nba, wnba).
     */
    protected string $league;

    /**
     * Clase del modelo correspondiente.
     */
    protected string $model;

    /**
     * Liga por defecto a usar si no se pasa una explícita.
     * Cada clase hija debe definir esto.
     */
    protected string $defaultLeague;

    /**
     * Mapa de ligas => modelos.
     * Cada clase hija debe definir esto.
     */
    protected array $modelMap = [];

    /**
     * Constructor con liga opcional.
     */
    public function __construct(?string $league = null)
    {
        $this->initializeLeague($league ?? $this->defaultLeague);
    }

    /**
     * Establece la liga y configura el modelo correspondiente.
     */
    protected function initializeLeague(string $league): void
    {
        if (!isset($this->modelMap[$league])) {
            throw new InvalidArgumentException("La liga '{$league}' no está soportada.");
        }

        $this->league = $league;
        $this->model = $this->modelMap[$league];
    }

    public function setLeague(string $league): void
    {
        $this->initializeLeague($league);
    }

    public function getLeague(): string
    {
        return $this->league;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function query(): Builder
    {
        return $this->model::query();
    }
}
