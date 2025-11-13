<?php

namespace Tests\Unit\Services\PDF;

use App\Models\ApplicationState;
use App\Services\PDF\PDFValidationService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PDFValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PDFValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PDFValidationService();
    }

    public function test_validates_complete_application_state()
    {
        $applicationState = ApplicationState::factory()->create([
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'emailAddress' => 'john.doe@example.com',
                    'mobile' => '+263771234567',
                    'nationalIdNumber' => '12-345678-A-12',
                ]
            ]
        ]);

        $errors = $this->service->validateApplicationState($applicationState);

        $this->assertEmpty($errors);
    }

    public function test_validation_fails_for_incomplete_application()
    {
        $applicationState = ApplicationState::factory()->create([
            'current_step' => 'form',
            'form_data' => []
        ]);

        $errors = $this->service->validateApplicationState($applicationState);

        $this->assertNotEmpty($errors);
        $this->assertContains('Application is not completed', $errors);
    }

    public function test_validation_fails_for_missing_form_data()
    {
        $applicationState = ApplicationState::factory()->create([
            'current_step' => 'completed',
            'form_data' => null
        ]);

        $errors = $this->service->validateApplicationState($applicationState);

        $this->assertNotEmpty($errors);
        $this->assertContains('Form data is missing', $errors);
    }

    public function test_validates_required_personal_information()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertEmpty($errors);
    }

    public function test_validation_fails_for_missing_required_fields()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                // Missing lastName, email, etc.
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Last name is required', $errors);
        $this->assertContains('Email address is required', $errors);
    }

    public function test_validates_email_format()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'invalid-email',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid email address format', $errors);
    }

    public function test_validates_phone_number_format()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => 'invalid-phone',
                'nationalIdNumber' => '12-345678-A-12',
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid mobile number format', $errors);
    }

    public function test_validates_national_id_format()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => 'invalid-id',
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid national ID number format', $errors);
    }

    public function test_validates_loan_amount_range()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
                'loanAmount' => 50,  // Too low
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'account_holder_loan_application.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Loan amount must be between $100 and $100,000', $errors);
    }

    public function test_validates_age_requirement()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
                'dateOfBirth' => now()->subYears(17)->format('Y-m-d'), // Under 18
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Applicant must be at least 18 years old', $errors);
    }

    public function test_validates_business_information_for_sme()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
                'businessName' => 'Test Business',
                'businessRegistrationNumber' => 'BR123456',
                'businessType' => 'retail',
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'smes_business_account_opening.json');

        $this->assertEmpty($errors);
    }

    public function test_validation_fails_for_missing_business_info()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
                // Missing business information
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'smes_business_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Business name is required', $errors);
        $this->assertContains('Business registration number is required', $errors);
    }

    public function test_validates_pdf_environment()
    {
        $errors = $this->service->validatePDFEnvironment();

        // Should pass in test environment
        $this->assertIsArray($errors);
    }

    public function test_validates_document_requirements()
    {
        $formData = [
            'documents' => [
                'uploadedDocuments' => [
                    'national_id' => [
                        [
                            'name' => 'id_front.jpg',
                            'path' => 'documents/id_front.jpg',
                            'type' => 'image/jpeg',
                            'size' => 1024000,
                        ]
                    ]
                ]
            ]
        ];

        $errors = $this->service->validateDocuments($formData);

        $this->assertEmpty($errors);
    }

    public function test_validation_fails_for_missing_required_documents()
    {
        $formData = [
            'documents' => []
        ];

        $errors = $this->service->validateDocuments($formData);

        $this->assertNotEmpty($errors);
        $this->assertContains('National ID document is required', $errors);
    }

    public function test_validates_document_file_types()
    {
        $formData = [
            'documents' => [
                'uploadedDocuments' => [
                    'national_id' => [
                        [
                            'name' => 'id_front.txt',
                            'path' => 'documents/id_front.txt',
                            'type' => 'text/plain',
                            'size' => 1024,
                        ]
                    ]
                ]
            ]
        ];

        $errors = $this->service->validateDocuments($formData);

        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid file type for national ID document', $errors);
    }

    public function test_validates_document_file_sizes()
    {
        $formData = [
            'documents' => [
                'uploadedDocuments' => [
                    'national_id' => [
                        [
                            'name' => 'id_front.jpg',
                            'path' => 'documents/id_front.jpg',
                            'type' => 'image/jpeg',
                            'size' => 10 * 1024 * 1024, // 10MB - too large
                        ]
                    ]
                ]
            ]
        ];

        $errors = $this->service->validateDocuments($formData);

        $this->assertNotEmpty($errors);
        $this->assertContains('File size too large for national ID document', $errors);
    }

    public function test_gets_validation_rules_for_form_type()
    {
        $rules = $this->service->getValidationRules('individual_account_opening.json');

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('personal_information', $rules);
        $this->assertArrayHasKey('contact_information', $rules);
    }

    public function test_validates_specific_field()
    {
        $isValid = $this->service->validateField('emailAddress', 'john.doe@example.com', 'email');
        $this->assertTrue($isValid);

        $isValid = $this->service->validateField('emailAddress', 'invalid-email', 'email');
        $this->assertFalse($isValid);
    }

    public function test_validates_conditional_fields()
    {
        // Test that spouse information is required when married
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
                'maritalStatus' => 'married',
                // Missing spouse information
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Spouse name is required when married', $errors);
    }
}
