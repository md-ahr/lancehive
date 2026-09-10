<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Route Version
    |--------------------------------------------------------------------------
    |
    | URL segment for versioned API routes. Laravel's api router adds the "api"
    | prefix; this value adds the version segment (e.g. /api/v1/login).
    |
    */

    'route_version' => env('API_ROUTE_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | API Path Prefix
    |--------------------------------------------------------------------------
    |
    | Full path prefix after the domain. Used by Scramble and test helpers.
    |
    */

    'prefix' => 'api/'.env('API_ROUTE_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Versioned Feature Route Directory
    |--------------------------------------------------------------------------
    |
    | Feature route files for the active API version (e.g. routes/features/v1/).
    |
    */

    'features_routes' => base_path('routes/features/'.env('API_ROUTE_VERSION', 'v1')),

];
