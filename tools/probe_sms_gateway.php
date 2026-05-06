<?php
require __DIR__ . '/..\vendor\autoload.php';

$app = require_once __DIR__ . '/..\bootstrap\app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function probeUrl($url, $login = null, $password = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($login) {
        curl_setopt($ch, CURLOPT_USERPWD, $login . ':' . $password);
    }

    $start = microtime(true);
    $res = curl_exec($ch);
    $time = round((microtime(true) - $start) * 1000);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [
        'url' => $url,
        'http_code' => $httpCode,
        'time_ms' => $time,
        'error' => $err
    ];
}

$gateway = config('sms.gateway_url');
$login = config('sms.login');
$password = config('sms.password');

$variants = [];
$variants[] = $gateway;

// If gateway contains :443 with http, try https without :443
if (strpos($gateway, ':443') !== false) {
    $variants[] = str_replace(':443', '', $gateway);
    $variants[] = preg_replace('/^http:/i', 'https:', str_replace(':443', '', $gateway));
}

// Also try forcing https
$variants[] = preg_replace('/^http:/i', 'https:', $gateway);

// Try with common API prefixes
$variants[] = rtrim($gateway, '/') . '/message';
$variants[] = rtrim($gateway, '/') . '/messages';
$variants[] = rtrim($gateway, '/') . '/api/message';
$variants[] = rtrim($gateway, '/') . '/api/messages';

// Also try the same paths without any explicit port if present
if (strpos($gateway, ':') !== false) {
    $baseNoPort = preg_replace('/:\d+/', '', rtrim($gateway, '/'));
    $variants[] = $baseNoPort . '/message';
    $variants[] = $baseNoPort . '/messages';
    $variants[] = $baseNoPort . '/api/message';
    $variants[] = $baseNoPort . '/api/messages';
}

// Also try https variants explicitly
$httpsBase = preg_replace('/^http:/i', 'https:', rtrim($gateway, '/'));
$variants[] = $httpsBase . '/message';
$variants[] = $httpsBase . '/messages';
$variants[] = $httpsBase . '/api/message';
$variants[] = $httpsBase . '/api/messages';

$variants = array_values(array_unique($variants));

echo "Probing SMS gateway variants (login: " . ($login ? 'set' : 'not set') . "):\n\n";

foreach ($variants as $v) {
    echo "-> Testing: {$v}\n";
    $r = probeUrl($v, $login, $password);
    echo "   HTTP: {$r['http_code']}, time: {$r['time_ms']}ms";
    if ($r['error']) echo ", error: {$r['error']}";
    echo "\n";
}

exit(0);
