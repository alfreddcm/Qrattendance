<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Android SMS Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Android SMS Gateway settings for your
    | application. These values are used to connect to your Android device
    | running the SMS Gateway application.
    |
    */

    'gateway_url' => env('SMS_GATEWAY_URL', 'http://192.168.1.100:8080'),
    'login' => env('SMS_GATEWAY_LOGIN'),
    'password' => env('SMS_GATEWAY_PASSWORD'),
    
    /*
    |--------------------------------------------------------------------------
    | SMS Settings
    |--------------------------------------------------------------------------
    |
    | Configure SMS-specific settings such as timeouts and validation rules.
    |
    */
    
    'timeout' => 30, // Request timeout in seconds
    'validate_numbers' => true, // Whether to validate phone numbers
    'allowed_country_codes' => ['+63'], // Allowed country codes
    'default_country_code' => '+63', // Default country code for local numbers
    
    /*
    |--------------------------------------------------------------------------
    | Sender ID Configuration
    |--------------------------------------------------------------------------
    |
    | Configure custom sender ID to display instead of phone number.
    | Note: Sender ID support depends on your mobile carrier.
    |
    */
    
    'sender_id' => env('SMS_SENDER_ID', 'Scan-to-notify'),
    'use_sender_id' => env('SMS_USE_SENDER_ID', true),
];
