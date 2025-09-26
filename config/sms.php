<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your SMS service settings here.
    | This application uses Twilio for SMS functionality.
    |
    */

    'enabled' => env('SMS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    |
    | These are pulled from the services.twilio config
    |
    */

    'provider' => 'twilio',

    /*
    |--------------------------------------------------------------------------
    | SMS Service Settings
    |--------------------------------------------------------------------------
    |
    | General SMS behavior configuration
    |
    */

    'retry_attempts' => env('SMS_RETRY_ATTEMPTS', 3),

    'timeout' => env('SMS_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    |
    | Default templates for common SMS types
    |
    */

    'templates' => [
        'booking_confirmed' => 'Dear {name}, your booking #{booking_id} has been confirmed. Thank you for choosing our service!',
        'booking_reminder' => 'Reminder: Your booking #{booking_id} starts on {date}. Safe travels!',
        'booking_cancelled' => 'Dear {name}, your booking #{booking_id} has been cancelled. Contact us for assistance.',
        'traffic_check' => 'JPJ SAMAN {plate_number}',
    ],
];
