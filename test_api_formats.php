<?php

echo "Testing capcom6/android-sms-gateway API Formats\n";
echo "==============================================\n\n";

$baseUrl = 'http://192.168.68.121:8080';
$auth = base64_encode('qrsms:qrsms123');

// Based on capcom6/android-sms-gateway repository, let's try different formats
$testFormats = [
    // Format 1: Standard format from documentation
    [
        'name' => 'Standard format',
        'data' => [
            'phone' => '+639261074274',
            'message' => 'Test message 1'
        ]
    ],
    // Format 2: Without + in phone number
    [
        'name' => 'Without + prefix',
        'data' => [
            'phone' => '639261074274',
            'message' => 'Test message 2'
        ]
    ],
    // Format 3: With simId
    [
        'name' => 'With simId',
        'data' => [
            'phone' => '+639261074274',
            'message' => 'Test message 3',
            'simId' => 0
        ]
    ],
    // Format 4: With delivery report
    [
        'name' => 'With delivery report',
        'data' => [
            'phone' => '+639261074274',
            'message' => 'Test message 4',
            'withDeliveryReport' => false
        ]
    ],
    // Format 5: Simple short message
    [
        'name' => 'Simple short',
        'data' => [
            'phone' => '+639261074274',
            'message' => 'Hi'
        ]
    ]
];

foreach ($testFormats as $index => $format) {
    echo ($index + 1) . ". Testing: " . $format['name'] . "\n";
    
    $jsonData = json_encode($format['data']);
    echo "   Data: $jsonData\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'method' => 'POST',
            'header' => "Authorization: Basic $auth\r\n" .
                       "Content-Type: application/json\r\n" .
                       "Content-Length: " . strlen($jsonData) . "\r\n",
            'content' => $jsonData
        ]
    ]);
    
    $start = microtime(true);
    $response = @file_get_contents($baseUrl . '/messages', false, $context);
    $end = microtime(true);
    
    echo "   Time: " . round(($end - $start), 2) . "s - ";
    
    if ($response !== false) {
        echo "✅ SUCCESS!\n";
        echo "   Response: $response\n";
        
        $json = json_decode($response, true);
        if ($json && isset($json['id'])) {
            echo "   Message ID: " . $json['id'] . "\n";
            echo "   🎉 THIS FORMAT WORKS! Use this for production.\n";
            
            // Test balance check with this working format
            echo "\n   Testing balance check with working format...\n";
            $balanceData = $format['data'];
            $balanceData['phone'] = '8080';
            $balanceData['message'] = 'bal';
            
            $balanceJson = json_encode($balanceData);
            $balanceContext = stream_context_create([
                'http' => [
                    'timeout' => 8,
                    'method' => 'POST',
                    'header' => "Authorization: Basic $auth\r\n" .
                               "Content-Type: application/json\r\n" .
                               "Content-Length: " . strlen($balanceJson) . "\r\n",
                    'content' => $balanceJson
                ]
            ]);
            
            $balanceResponse = @file_get_contents($baseUrl . '/messages', false, $balanceContext);
            if ($balanceResponse !== false) {
                echo "   ✅ Balance check also works: $balanceResponse\n";
            } else {
                echo "   ❌ Balance check failed (might not support short codes)\n";
            }
            
            break; // Stop testing once we find a working format
        }
    } else {
        echo "❌ Failed\n";
        $error = error_get_last();
        if (isset($error['message'])) {
            if (strpos($error['message'], '500') !== false) {
                echo "   Error: Internal Server Error (format issue)\n";
            } elseif (strpos($error['message'], '400') !== false) {
                echo "   Error: Bad Request (invalid data)\n";
            } else {
                echo "   Error: " . $error['message'] . "\n";
            }
        }
    }
    echo "\n";
}

echo "Recommendation:\n";
echo "- If none of these work, check the SMS Gateway app on your phone\n";
echo "- Look for error logs or messages in the app\n";
echo "- Try sending a message manually through the app interface\n";
echo "- Verify the app has proper SMS permissions\n";
