<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GST/HST on management fees
    |--------------------------------------------------------------------------
    | When enabled, HST at the configured rate is added on top of the
    | management fee and withheld from the owner payout, i.e.
    | net_to_owner = rent - management_fee - hst.
    |
    | Defaults come from env; they can be overridden at runtime from
    | Admin > Settings (stored in storage/app/tenanttrust-settings.json).
    */

    'hst' => [
        'enabled' => env('TT_HST_ENABLED', false),
        'rate'    => env('TT_HST_RATE', 13.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | PostGrid print & mail
    |--------------------------------------------------------------------------
    | Controls the "Outgoing Mail" admin section. Mode can be overridden at
    | runtime from Admin > Settings (storage/app/tenanttrust-settings.json).
    | Keys default to the env vars loaded from the workspace .env.
    */

    'postgrid' => [
        'mode'     => env('TT_POSTGRID_MODE', 'test'),
        'test_key' => env('POSTGRID_TEST_KEY'),
        'live_key' => env('POSTGRID_LIVE_KEY'),
    ],

];
