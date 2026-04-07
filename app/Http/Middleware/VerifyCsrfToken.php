<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Public attendance scanning routes - these don't require CSRF since they're public
        'public/attendance/scan-qr',
        'public/attendance/*/clear',
    ];
}
