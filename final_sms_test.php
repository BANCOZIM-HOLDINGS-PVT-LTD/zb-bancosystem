<?php
try {
    echo "--------------------------------------------------\n";
    echo "SMS Provider Verification Script\n";
    
    // Resolve the service
    $provider = app(\App\Contracts\SmsProviderInterface::class);
    
    // Target number
    $to = '263779890173';
    $message = "Test SMS from MicroBiz - Integration Verified " . date('H:i:s');
    
    echo "Sending to: $to\n";

    // Send
    $result = $provider->sendSms($to, $message);
    
    echo "Result:\n";
    print_r($result);
    echo "--------------------------------------------------\n";
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
