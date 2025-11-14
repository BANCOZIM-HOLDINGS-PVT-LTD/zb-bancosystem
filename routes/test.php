<?php

use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use Illuminate\Support\Facades\Route;

// Test PDF generation route
Route::get('/test-pdf/{sessionId?}', function ($sessionId = null) {
    try {
        // Use test session ID if none provided
        $sessionId = $sessionId ?: 'test_1752244141';

        $state = ApplicationState::where('session_id', $sessionId)->first();

        if (! $state) {
            return response()->json([
                'error' => 'Application not found',
                'session_id' => $sessionId,
                'available' => ApplicationState::pluck('session_id')->take(5),
            ], 404);
        }

        $pdfGenerator = new PDFGeneratorService;
        $pdfPath = $pdfGenerator->generateApplicationPDF($state);

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'pdf_path' => $pdfPath,
            'download_url' => url("/application/download/{$sessionId}"),
            'view_url' => url("/application/view/{$sessionId}"),
            'file_exists' => \Storage::disk('public')->exists($pdfPath),
            'file_size' => \Storage::disk('public')->exists($pdfPath) ? \Storage::disk('public')->size($pdfPath) : 0,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Test SSB PDF generation route with comprehensive data
Route::get('/test-ssb-pdf', function () {
    try {
        // Create test application state with SSB employer
        $testState = new ApplicationState;
        $testState->id = 9999;
        $testState->session_id = 'test-ssb-pdf-'.time();
        $testState->reference_code = 'SSB-TEST-'.time();
        $testState->form_data = [
            'employer' => 'goz-ssb',
            'formResponses' => [
                'title' => 'Mr',
                'firstName' => 'John',
                'surname' => 'Doe',
                'gender' => 'Male',
                'dateOfBirth' => '1990-01-15',
                'maritalStatus' => 'Married',
                'nationality' => 'Zimbabwean',
                'idNumber' => '63-123456-A63',
                'cellNumber' => '+263712345678',
                'whatsApp' => '+263712345678',
                'emailAddress' => 'john.doe@example.com',
                'responsibleMinistry' => 'Education',
                'employerName' => 'Ministry of Education',
                'employerAddress' => 'Harare, Zimbabwe',
                'residentialAddress' => '123 Main Street, Harare',
                'permanentAddress' => '123 Main Street, Harare',
                'propertyOwnership' => 'Owned',
                'periodAtAddress' => '5',
                'employmentStatus' => 'Permanent',
                'jobTitle' => 'Teacher',
                'dateOfEmployment' => '2015-02-01',
                'employmentNumber' => 'EMP12345',
                'headOfInstitution' => 'Mr. Smith',
                'headOfInstitutionCell' => '+263712999888',
                'currentNetSalary' => '800',
                'bankName' => 'ZB Bank',
                'bankBranch' => 'Harare',
                'accountNumber' => '1234567890',
                'loanAmount' => '2000',
                'loanTenure' => '12',
                'creditFacilityType' => 'Solar System',
                'spouseDetails' => [
                    [
                        'fullName' => 'Jane Doe',
                        'relationship' => 'Spouse',
                        'phoneNumber' => '+263712345679',
                        'residentialAddress' => '123 Main Street, Harare',
                    ],
                ],
            ],
            'monthlyPayment' => '200',
            // Add signature data for testing
            'documents' => [
                'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ],
            'signatureImage' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ];

        $pdfGenerator = new PDFGeneratorService;

        // Generate the PDF
        $pdfContent = $pdfGenerator->generatePDF($testState);

        // Save it to a test file
        $filename = 'test_ssb_'.time().'.pdf';
        $filepath = 'applications/'.$filename;
        \Storage::disk('public')->put($filepath, $pdfContent);

        return response()->json([
            'success' => true,
            'message' => 'SSB PDF generated successfully!',
            'session_id' => $testState->session_id,
            'reference_code' => $testState->reference_code,
            'pdf_path' => $filepath,
            'file_size' => strlen($pdfContent),
            'download_url' => url('/storage/'.$filepath),
            'view_url' => url('/storage/'.$filepath),
            'file_exists' => \Storage::disk('public')->exists($filepath),
            'storage_file_size' => \Storage::disk('public')->size($filepath),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to generate SSB PDF',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString()),
        ], 500);
    }
});
