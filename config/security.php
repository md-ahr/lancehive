<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login lockout
    |--------------------------------------------------------------------------
    |
    | Per-account brute-force protection layered on top of IP rate limiting.
    | Failed attempts increment for existing users only; lockout uses the same
    | generic login error message to avoid account enumeration.
    |
    */

    'login' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 10),
        'lockout_minutes' => (int) env('LOGIN_LOCKOUT_MINUTES', 15),
    ],

];
