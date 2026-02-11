<?php
use Illuminate\Support\Facades\Http;

$token = config('services.codel.token');
$senderId = 'MicroBiz';
$destination = '263779890173'; 

$output = "Testing Sender ID variations...\n";

// Test 1: v2 API with sender_id
$output .= "\n--- Test 1: v2 API with sender_id ---\n";
try {
    $response = Http::post('https://2wcapi.codel.tech/2wc/single-sms/v2/api', [
        'token' => $token,
        'sender_id' => $senderId,
        'destination' => $destination,
        'messageText' => 'Test 1: sender_id',
        'messageReference' => uniqid(),
        'messageDate' => '',
        'messageValidity' => '',
        'sendDateTime' => ''
    ]);
    $output .= "Status: " . $response->status() . "\n";
    $output .= "Body: " . $response->body() . "\n";
} catch (\Exception $e) {
    $output .= "Error: " . $e->getMessage() . "\n";
}

// Test 2: v2 API with senderID
$output .= "\n--- Test 2: v2 API with senderID ---\n";
try {
    $response = Http::post('https://2wcapi.codel.tech/2wc/single-sms/v2/api', [
        'token' => $token,
        'senderID' => $senderId,
         'destination' => $destination,
        'messageText' => 'Test 2: senderID',
        'messageReference' => uniqid(),
        'messageDate' => '',
        'messageValidity' => '',
        'sendDateTime' => ''
    ]);
    $output .= "Status: " . $response->status() . "\n";
    $output .= "Body: " . $response->body() . "\n";
} catch (\Exception $e) {
    $output .= "Error: " . $e->getMessage() . "\n";
}


file_put_contents('test_output.txt', $output);
echo "Done writing to test_output.txt\n";
