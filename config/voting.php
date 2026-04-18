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

    /*
    |--------------------------------------------------------------------------
    | Voter OTP (planned — see VerifyVoterIdentityAction)
    |--------------------------------------------------------------------------
    | When `enabled` is true, identity match alone is not enough — the voter
    | must also confirm a one-time code sent to their registered mobile.
    | Stays disabled by default until an SMS provider is wired up.
    */
    'voter_otp' => [
        'enabled'     => env('VOTER_OTP_ENABLED', false),
        'length'      => 6,
        'ttl_seconds' => 300,
        'max_attempts'=> 5,
    ],

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
