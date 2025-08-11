<?php

echo "Testing Correct capcom6 API Format\n";
echo "===================================\n\n";

$baseUrl = 'http://192.168.68.121:8080';
$auth = base64_encode('qrsms:qrsms123');

echo "1. Testing with your phone number using correct format...\n";

// Use the correct capcom6 format
$correctData = json_encode([
    'textMessage' => [
        'text' => 'Hello! This is a test message from your Laravel SMS Gateway! 📱✅'
    ],
    'phoneNumbers' => ['+639261074274']
]);

echo "Data: $correctData\n\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'method' => 'POST',
        'header' => "Authorization: Basic $auth\r\n" .
                   "Content-Type: application/json\r\n" .
                   "Content-Length: " . strlen($correctData) . "\r\n",
        'content' => $correctData
    ]
]);

$start = microtime(true);
$response = @file_get_contents($baseUrl . '/message', false, $context);  // Note: /message not /messages
$end = microtime(true);

echo "Response time: " . round(($end - $start), 2) . " seconds\n";

if ($response !== false) {
    echo "✅ SUCCESS! Response: $response\n";
    
    $json = json_decode($response, true);
    if ($json) {
        echo "Parsed response: " . json_encode($json, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($json['id'])) {
            echo "🎉 Message ID: " . $json['id'] . "\n";
            echo "📱 Check your phone for the SMS!\n\n";
            
            echo "2. Now testing balance check to 8080...\n";
            
            $balanceData = json_encode([
                'textMessage' => [
                    'text' => 'bal'
                ],
                'phoneNumbers' => ['8080']
            ]);
            
            echo "Balance check data: $balanceData\n";
            
            $balanceContext = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'method' => 'POST',
                    'header' => "Authorization: Basic $auth\r\n" .
                               "Content-Type: application/json\r\n" .
                               "Content-Length: " . strlen($balanceData) . "\r\n",
                    'content' => $balanceData
                ]
            ]);
            
            $balanceResponse = @file_get_contents($baseUrl . '/message', false, $balanceContext);
            
            if ($balanceResponse !== false) {
                echo "✅ Balance check SUCCESS: $balanceResponse\n";
                $balanceJson = json_decode($balanceResponse, true);
                if ($balanceJson && isset($balanceJson['id'])) {
                    echo "🎉 Balance check sent! Message ID: " . $balanceJson['id'] . "\n";
                    echo "📱 Check your phone for balance response from your mobile provider!\n";
                }
            } else {
                echo "❌ Balance check failed\n";
                $error = error_get_last();
                echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
            }
        }
    }
} else {
    echo "❌ Failed\n";
    $error = error_get_last();
    echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
}

echo "\n3. Summary:\n";
echo "- If you received the test SMS, the gateway is working perfectly!\n";
echo "- The correct API format is: /message endpoint with textMessage and phoneNumbers\n";
echo "- Your Laravel SMS service has been updated to use the correct format\n";
