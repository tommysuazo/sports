<?php 

return [
    // Temporada NBA 2025-26
    'start_date' => '2025-10-21',  // inicio de temporada regular
    'end_date'   => '2026-04-12',  // fin de temporada regular

    // Días a excluir (no hay jornada regular)
    'exclude_dates' => [
        // No hay jornada regular por Thanksgiving (EE. UU.)
        '2025-11-27',

        // All-Star Weekend 2026 (Los Ángeles, Intuit Dome)
        '2026-02-13', // Rising Stars
        '2026-02-14', // All-Star Saturday Night
        '2026-02-15', // All-Star Game
    ],
];
