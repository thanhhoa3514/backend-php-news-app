<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Length
    |--------------------------------------------------------------------------
    |
    | The length of the OTP code (default: 6 digits)
    |
    */
    'length' => env('OTP_LENGTH', 6),

    /*
    |--------------------------------------------------------------------------
    | OTP Expiry
    |--------------------------------------------------------------------------
    |
    | The time in minutes before the OTP expires (default: 10 minutes)
    |
    */
    'expiry' => env('OTP_EXPIRY', 10),

    /*
    |--------------------------------------------------------------------------
    | Max Attempts
    |--------------------------------------------------------------------------
    |
    | Maximum number of OTP generation attempts per hour (default: 3)
    |
    */
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),

];

