<?php

use Illuminate\Foundation\Application;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Debugging Phone Number Validation\n";
echo "=================================\n\n";

$smsService = app('App\Services\AndroidSmsGatewayService');

// Test different numbers
$testNumbers = ['8080', '123', '12345', '+639123456789', '09123456789'];

echo "Testing phone number validation:\n";

$reflection = new ReflectionClass($smsService);
$validateMethod = $reflection->getMethod('isValidPhoneNumber');
$validateMethod->setAccessible(true);

$normalizeMethod = $reflection->getMethod('normalizePhoneNumber');
$normalizeMethod->setAccessible(true);

foreach ($testNumbers as $number) {
    echo "Testing: '$number'\n";
    
    $normalized = $normalizeMethod->invoke($smsService, $number);
    echo "  Normalized: '$normalized'\n";
    
    $isValid = $validateMethod->invoke($smsService, $normalized);
    echo "  Valid: " . ($isValid ? 'Yes' : 'No') . "\n";
    
    echo "\n";
}

echo "Configuration check:\n";
echo "Validate numbers: " . (config('sms.validate_numbers') ? 'true' : 'false') . "\n";

// Test the actual sendSms method step by step
echo "\nTesting sendSms with '8080':\n";

try {
    // Simulate the validation logic from sendSms
    $recipients = ['8080'];
    $validRecipients = [];
    
    foreach ($recipients as $recipient) {
        echo "Processing recipient: '$recipient'\n";
        
        $normalizedNumber = $normalizeMethod->invoke($smsService, $recipient);
        echo "  Normalized: '$normalizedNumber'\n";
        
        if ($validateMethod->invoke($smsService, $normalizedNumber)) {
            $validRecipients[] = $normalizedNumber;
            echo "  ✓ Added to valid recipients\n";
        } else {
            echo "  ✗ Rejected - not valid\n";
        }
    }
    
    echo "Valid recipients: " . implode(', ', $validRecipients) . "\n";
    echo "Count: " . count($validRecipients) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
