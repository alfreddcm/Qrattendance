<?php

echo "SMS Gateway Diagnostic Tool\n";
echo "===========================\n\n";

$baseUrl = 'http://192.168.68.121:8080';
$auth = base64_encode('qrsms:qrsms123');

echo "1. Basic connectivity test...\n";
$response = @file_get_contents($baseUrl, false, stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET',
        'header' => "Authorization: Basic $auth\r\n"
    ]
]));

if ($response) {
    echo "   ✅ Gateway responds: $response\n";
} else {
    echo "   ❌ Gateway not responding\n";
    exit(1);
}

echo "\n2. Testing different endpoints...\n";
$endpoints = ['/', '/status', '/health', '/info', '/messages'];

foreach ($endpoints as $endpoint) {
    $url = $baseUrl . $endpoint;
    $response = @file_get_contents($url, false, stream_context_create([
        'http' => [
            'timeout' => 3,
            'method' => 'GET',
            'header' => "Authorization: Basic $auth\r\n"
        ]
    ]));
    
    if ($response !== false) {
        echo "   ✅ $endpoint: $response\n";
    } else {
        echo "   ❌ $endpoint: No response\n";
    }
}

echo "\n3. Testing with minimal POST data...\n";

// Try the absolute minimum data
$minimalTests = [
    '{}',
    '{"test": "value"}',
    '{"phone": "test"}',
    '{"message": "test"}'
];

foreach ($minimalTests as $testData) {
    echo "   Testing: $testData\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'POST',
            'header' => "Authorization: Basic $auth\r\n" .
                       "Content-Type: application/json\r\n" .
                       "Content-Length: " . strlen($testData) . "\r\n",
            'content' => $testData
        ]
    ]);
    
    $response = @file_get_contents($baseUrl . '/messages', false, $context);
    
    if ($response !== false) {
        echo "     ✅ Response: $response\n";
    } else {
        $error = error_get_last();
        if (strpos($error['message'], '500') !== false) {
            echo "     ❌ 500 Error\n";
        } elseif (strpos($error['message'], '400') !== false) {
            echo "     ❌ 400 Error\n";
        } else {
            echo "     ❌ Other error\n";
        }
    }
}

echo "\n4. Manual steps to try:\n";
echo "   a) Check your phone's SMS Gateway app:\n";
echo "      - Is it still running?\n";
echo "      - Any error messages?\n";
echo "      - Try restarting the app\n\n";

echo "   b) Try the web interface:\n";
echo "      - Open http://192.168.68.121:8080 in your browser\n";
echo "      - Login with qrsms / qrsms123\n";
echo "      - Try sending a test SMS through the web interface\n\n";

echo "   c) Check app permissions:\n";
echo "      - SMS permissions\n";
echo "      - Phone permissions\n";
echo "      - Make sure app isn't being killed by battery optimization\n\n";

echo "   d) Try different SMS Gateway apps:\n";
echo "      - 'SMS Gateway API' (different app)\n";
echo "      - 'HTTP SMS Gateway'\n";
echo "      - Some might have different API formats\n";
