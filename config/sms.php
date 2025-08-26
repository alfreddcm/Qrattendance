<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Android SMS Gateway Configuration
    |--------------------------------------------------------------------------
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
    */
    
    'timeout' => 30,  
    'validate_numbers' => true, 
    'allowed_country_codes' => ['+63'],  
    'default_country_code' => '+63',
    'sender_id' => env('SMS_SENDER_ID', 'Scan-to-notify'),  // Default sender ID
    
    /*
    |--------------------------------------------------------------------------
    | Message Rate Limiting
    |--------------------------------------------------------------------------
    |
    */
    
    'message_delay_seconds' => env('SMS_MESSAGE_DELAY_SECONDS', 60), 
    'enable_rate_limiting' => env('SMS_ENABLE_RATE_LIMITING', true),
];
