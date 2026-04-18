<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination sizes
    |--------------------------------------------------------------------------
    | Each list screen used to hard-code its own per_page value. Centralising
    | them here lets ops tune memory footprint in one place.
    */
    'pagination' => [
        'campaigns' => 15,
        'clubs'     => 15,
        'players'   => 20,
        'users'     => 20,
        'results'   => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV import limits
    |--------------------------------------------------------------------------
    */
    'import' => [
        'max_size_kb' => 5120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Team of the Season formation
    |--------------------------------------------------------------------------
    | Single source of truth for the formation math. Historically these
    | lived on the domain class as constants; moving them into config means
    | they can be tuned without recompiling opcache and stay decoupled from
    | the domain layer (no UI or localisation concerns).
    */
    'team_of_the_season' => [
        'goalkeeper_count' => 1,
        'outfield_total'   => 10,
        'total'            => 11,
        'min_line'         => 2,
        'max_line'         => 6,
        'default_attack'   => 3,
        'default_midfield' => 3,
        'default_defense'  => 4,
    ],

];
