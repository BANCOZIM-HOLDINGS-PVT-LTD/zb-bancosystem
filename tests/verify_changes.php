<?php

use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Observers\ApplicationStateObserver;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function testAppNumbers() {
    echo "\nTesting Application Number Prefixes (Model Attribute)...\n";
    
    $scenarios = [
        'SSB' => ['employer' => 'government-ssb', 'formResponses' => ['employerType' => 'government']],
        'Account Holder' => ['hasAccount' => true, 'employer' => 'Private'],
        'Pensioner' => ['employer' => 'Pension Department', 'formResponses' => ['employmentStatus' => 'pensioner']],
        'RDC' => ['employer' => 'Mutare RDC'],
        'SME' => ['formId' => 'sme_loan', 'employer' => 'Self'],
        'Default' => ['employer' => 'Private Limited'],
    ];

    foreach ($scenarios as $type => $data) {
        $state = new ApplicationState();
        $state->id = 123;
        $state->form_data = $data;
        // Mock created_at
        $state->created_at = now(); 
        
        $result = $state->application_number;
        echo "Type: $type -> Result: $result\n";
    }
}

function testPersonalServices() {
    echo "\nTesting Personal Service Detection...\n";
    $observer = new ApplicationStateObserver();
    $reflector = new ReflectionClass($observer);
    $method = $reflector->getMethod('determineServiceType');
    $method->setAccessible(true);
    
    $isPersonalMethod = $reflector->getMethod('isPersonalService');
    $isPersonalMethod->setAccessible(true);

    $scenarios = [
        'Chicken Project' => ['category' => 'Agriculture', 'business' => 'Poultry', 'productName' => 'Broiler Starter'],
        'Groceries' => ['category' => 'Retail', 'business' => 'Tuckshop', 'productName' => 'Grocery Hamper'],
        'Building' => ['category' => 'Construction', 'business' => 'Hardware', 'productName' => 'Cement 50kg'],
        'School Fees' => ['category' => 'Education', 'productName' => 'School Fees'],
        'Regular Loan' => ['category' => 'General', 'productName' => 'Cash Loan'],
    ];

    foreach ($scenarios as $type => $data) {
        $state = new ApplicationState();
        $state->form_data = $data;
        
        $isPersonal = $isPersonalMethod->invoke($observer, $state);
        $serviceType = $method->invoke($observer, $data);
        
        echo "Scenario: $type -> IsPersonal: " . ($isPersonal ? 'Yes' : 'No') . ", Type: $serviceType\n";
    }
}

function testPDFAddressOverride() {
    echo "\nTesting PDF Address Logic...\n";
    $service = new PDFGeneratorService();
    $reflector = new ReflectionClass($service);
    $method = $reflector->getMethod('preparePDFData');
    $method->setAccessible(true);
    
    // Mock ApplicationState
    $state = new ApplicationState();
    $state->session_id = 'test_session';
    $state->form_data = [
        'formResponses' => [
            'address' => '123 Residential St',
            'residentialAddress' => '123 Residential St',
            'city' => 'Harare'
        ],
        'deliverySelection' => [
            'agent' => 'Farm & City',
            'depot' => 'Harare Branch', 
            'city' => 'Harare'
        ]
    ];
    
    $data = $method->invoke($service, $state);
    
    echo "Residential Address: " . ($data['formResponses']['residentialAddress'] ?? 'N/A') . "\n";
    echo "Delivery Address (New Field): " . ($data['deliveryAddress'] ?? 'N/A') . "\n";
    
    if (($data['formResponses']['residentialAddress'] === '123 Residential St') && 
        str_contains($data['deliveryAddress'], 'Farm & City')) {
        echo "SUCCESS: Residential Address preserved and Delivery Address set.\n";
    } else {
        echo "FAILURE: Address logic incorrect.\n";
    }
}

try {
    testAppNumbers();
    testPersonalServices();
    testPDFAddressOverride();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
