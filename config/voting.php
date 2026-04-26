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
    | Voter session
    |--------------------------------------------------------------------------
    | A voter who has verified their identity gets a per-campaign session
    | entry. This TTL makes that entry expire so an unattended browser
    | cannot be used hours later by someone else. Re-verification is cheap
    | (one form submit) so a short window is safe.
    */
    'voter_session' => [
        'ttl_minutes' => 15,
    ],

    // voter_otp block removed (2026-04 dead-code sweep) — never wired
    // up, no consumer in the codebase. Re-add when an SMS OTP flow
    // actually ships.

    /*
    |--------------------------------------------------------------------------
    | CSV export safety
    |--------------------------------------------------------------------------
    | mask_pii_by_default → national_id and mobile are masked in player
    | exports (e.g. 102****78, 0501***567) so a routine download never leaks
    | full PII. Cells starting with =, +, -, @ or tab are always prefixed
    | with a single quote to neutralise CSV/Excel formula injection.
    */
    'export' => [
        'mask_pii_by_default' => true,
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
