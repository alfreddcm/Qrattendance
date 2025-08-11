<?php

echo "Quick SMS Test\n";
echo "==============\n\n";

// Test using direct HTTP call to see what's happening
$baseUrl = 'http://192.168.68.121:8080';
$auth = base64_encode('qrsms:qrsms123');

echo "1. Testing direct HTTP call...\n";

$smsData = json_encode([
    'phone' => '+639261074274',
    'message' => 'Quick test from Laravel'
]);

echo "Sending: $smsData\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'POST',
        'header' => "Authorization: Basic $auth\r\n" .
                   "Content-Type: application/json\r\n" .
                   "Content-Length: " . strlen($smsData) . "\r\n",
        'content' => $smsData
    ]
]);

$start = microtime(true);
$response = @file_get_contents($baseUrl . '/messages', false, $context);
$end = microtime(true);

echo "Response time: " . round(($end - $start), 2) . " seconds\n";

if ($response !== false) {
    echo "✅ Response received: $response\n";
    
    $json = json_decode($response, true);
    if ($json && isset($json['id'])) {
        echo "✅ Message ID: " . $json['id'] . "\n";
        echo "📱 Check your phone for the SMS!\n";
    }
} else {
    echo "❌ No response received\n";
    $error = error_get_last();
    echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
}

echo "\n2. Quick balance test to 8080...\n";

$balanceData = json_encode([
    'phone' => '8080',
    'message' => 'bal'
]);

echo "Sending: $balanceData\n";

$context2 = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'POST',
        'header' => "Authorization: Basic $auth\r\n" .
                   "Content-Type: application/json\r\n" .
                   "Content-Length: " . strlen($balanceData) . "\r\n",
        'content' => $balanceData
    ]
]);

$start2 = microtime(true);
$response2 = @file_get_contents($baseUrl . '/messages', false, $context2);
$end2 = microtime(true);

echo "Response time: " . round(($end2 - $start2), 2) . " seconds\n";

if ($response2 !== false) {
    echo "✅ Balance check response: $response2\n";
    
    $json2 = json_decode($response2, true);
    if ($json2 && isset($json2['id'])) {
        echo "✅ Balance check sent! Message ID: " . $json2['id'] . "\n";
        echo "📱 Check your phone for balance response!\n";
    }
} else {
    echo "❌ Balance check failed\n";
    $error2 = error_get_last();
    echo "Error: " . ($error2['message'] ?? 'Unknown error') . "\n";
}
