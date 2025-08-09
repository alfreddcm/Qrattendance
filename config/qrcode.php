<?php

return [
    /*
    |--------------------------------------------------------------------------
    | QR Code Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration determines the default settings for QR code generation.
    | You can override these settings when generating QR codes.
    |
    */

    'default' => [
        'format' => 'svg',
        'size' => 200,
        'margin' => 0,
        'encoding' => 'UTF-8',
        'errorCorrection' => 'M', // L, M, Q, H
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Backend
    |--------------------------------------------------------------------------
    |
    | This determines which image backend to use for generating QR codes.
    | Available options: 'gd', 'imagick', 'svg'
    |
    */

    'backend' => 'svg',
];
