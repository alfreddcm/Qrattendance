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
];
