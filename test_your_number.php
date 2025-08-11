<?php

use Illuminate\Foundation\Application;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing SMS Gateway with Your Phone Number\n";
echo "==========================================\n\n";

try {
    // Get the SMS service
    $smsService = app('App\Services\AndroidSmsGatewayService');
    
    echo "1. Testing gateway connectivity...\n";
    $gatewayInfo = $smsService->getGatewayInfo();
    
    if ($gatewayInfo['success']) {
        echo "   ✓ Gateway is reachable!\n";
        if (isset($gatewayInfo['data'])) {
            echo "   Device: " . ($gatewayInfo['data']['model'] ?? 'Unknown') . "\n";
            echo "   Status: " . ($gatewayInfo['data']['status'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "   ✗ Gateway not reachable: " . ($gatewayInfo['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
    // You can change this number to your actual phone number
    $yourPhoneNumber = '+639261074274'; // Replace with your number if different
    
    echo "\n2. Sending test SMS to $yourPhoneNumber...\n";
    
    // Send test message
    $testMessage = "Hello! This is a test message from your Laravel QR Attendance System. SMS Gateway is working! 📱✅";
    $result = $smsService->sendSms($testMessage, $yourPhoneNumber);
    
    if ($result['success']) {
        echo "   ✅ TEST SMS SENT SUCCESSFULLY!\n";
        echo "   Message ID: " . $result['message_id'] . "\n";
        echo "   Status: " . $result['status'] . "\n";
        echo "   Recipients: " . implode(', ', $result['recipients']) . "\n";
        
        echo "\n   📱 Check your phone:\n";
        echo "   - You should receive the test message\n";
        echo "   - This confirms the SMS gateway is working properly\n";
        
        // Wait and check status
        echo "\n3. Checking message status in 5 seconds...\n";
        sleep(5);
        
        $statusResult = $smsService->getStatus($result['message_id']);
        if ($statusResult['success']) {
            echo "   Message Status: " . $statusResult['status'] . "\n";
            if (isset($statusResult['data'])) {
                echo "   Status Details: " . json_encode($statusResult['data'], JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "   Status check: " . ($statusResult['error'] ?? 'Could not retrieve status') . "\n";
        }
        
        echo "\n4. Now testing balance check to 8080...\n";
        
        // Now try the balance check
        $balanceResult = $smsService->sendSms('bal', '8080');
        
        if ($balanceResult['success']) {
            echo "   ✅ BALANCE CHECK SENT SUCCESSFULLY!\n";
            echo "   Message ID: " . $balanceResult['message_id'] . "\n";
            echo "   Recipients: " . implode(', ', $balanceResult['recipients']) . "\n";
            echo "\n   📱 Check your phone for balance response from your mobile provider\n";
        } else {
            echo "   ❌ Balance check failed: " . ($balanceResult['error'] ?? 'Unknown error') . "\n";
            echo "   This might be because the gateway doesn't support short codes\n";
        }
        
    } else {
        echo "   ❌ Failed to send test SMS!\n";
        echo "   Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        
        echo "\n   Debug Info:\n";
        echo "   Gateway URL: " . config('sms.gateway_url') . "\n";
        echo "   Recipients attempted: " . json_encode($result['recipients'] ?? []) . "\n";
        
        // Try a simpler message
        echo "\n   Trying with a simpler message...\n";
        $simpleResult = $smsService->sendSms('Test', $yourPhoneNumber);
        
        if ($simpleResult['success']) {
            echo "   ✅ Simple message sent successfully!\n";
            echo "   Message ID: " . $simpleResult['message_id'] . "\n";
        } else {
            echo "   ❌ Simple message also failed: " . ($simpleResult['error'] ?? 'Unknown error') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n==========================================\n";
echo "Test completed!\n";

echo "\nNext steps if successful:\n";
echo "1. ✅ SMS Gateway is working - you can now use it for attendance notifications\n";
echo "2. 🎯 Teachers can send SMS from the attendance dashboard\n";
echo "3. 📊 All SMS will be logged in the outbound_messages table\n";
echo "4. 🔄 You can check delivery status for each message\n";
