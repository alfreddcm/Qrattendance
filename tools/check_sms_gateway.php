<?php

require __DIR__ . '/..\vendor\autoload.php';

$app = require_once __DIR__ . '/..\bootstrap\app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AndroidSmsGatewayService;
use Illuminate\Support\Facades\Log;

$service = new AndroidSmsGatewayService();

echo "Configured gateway URL: " . config('sms.gateway_url') . "\n";
echo "Gateway login: " . (config('sms.login') ? 'configured' : 'not set') . "\n";
echo "Timeout: " . config('sms.timeout') . "s\n";

echo "Checking gateway reachable via isGatewayReachable()...\n";
$reachable = $service->isGatewayReachable() ? 'reachable' : 'unreachable';
echo "Result: {$reachable}\n";

echo "Fetching gateway info (getGatewayInfo)...\n";
$info = $service->getGatewayInfo();
echo "getGatewayInfo result: \n";
print_r($info);

exit(0);
