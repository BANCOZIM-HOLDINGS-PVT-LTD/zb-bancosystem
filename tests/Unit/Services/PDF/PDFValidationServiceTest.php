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
    private $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = \Mockery::mock(\App\Services\PDFLoggingService::class)->shouldIgnoreMissing();
        $this->service = new PDFValidationService($this->loggerMock);
    }

    public function test_validates_complete_application_state()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test-session-id',
            'user_identifier' => 'test-user',
            'channel' => 'web',
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
        $applicationState = new ApplicationState([
            'session_id' => 'test-session-id',
            'user_identifier' => 'test-user',
            'channel' => 'web',
            'current_step' => 'form',
            'form_data' => ['some' => 'data']
        ]);

        $errors = $this->service->validateApplicationState($applicationState);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Application is not in a valid state', $errors[0] ?? '');
    }

    public function test_validation_fails_for_missing_form_data()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test-session-id',
            'user_identifier' => 'test-user',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => null
        ]);

        $errors = $this->service->validateApplicationState($applicationState);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('No form data found', $errors[0] ?? '');
    }

    public function test_validates_required_personal_information()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'surname' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
                'residentialAddress' => '123 Street',
                'dateOfBirth' => '1990-01-01',
                'maritalStatus' => 'Single'
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
                // Missing surname, email, etc.
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Required field 'surname' is missing", $errors[0] ?? '');
    }

    public function test_validates_email_format()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'surname' => 'Doe',
                'nationalIdNumber' => '12-345678-A-12',
                'mobile' => '+263771234567',
                'emailAddress' => 'invalid-email',
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
                'surname' => 'Doe',
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
                'surname' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => 'invalid#id',
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'individual_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid national ID number format', $errors);
    }

    public function test_validates_loan_amount_range()
    {
        $formData = [
            'amount' => 50,  // Too low
            'formResponses' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'account_holder_loan_application.json');

        $this->assertNotEmpty($errors);
        $this->assertContains('Loan amount must be at least $100', $errors);
    }

    public function test_validates_age_requirement()
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'John',
                'surname' => 'Doe',
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
                'surname' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
                'businessName' => 'Test Business',
                'businessRegistrationNumber' => 'BR123456',
                'businessAddress' => '123 Business St',
                'dateOfBirth' => '1990-01-01',
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
                'surname' => 'Doe',
                'emailAddress' => 'john.doe@example.com',
                'mobile' => '+263771234567',
                'nationalIdNumber' => '12-345678-A-12',
                // Missing business information
            ]
        ];

        $errors = $this->service->validateFormData($formData, 'smes_business_account_opening.json');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Required field 'businessName' is missing", $errors[0] ?? '');
    }

    public function test_validates_pdf_environment()
    {
        $errors = $this->service->validatePDFEnvironment();

        // Should pass in test environment
        $this->assertIsArray($errors);
    }
}
