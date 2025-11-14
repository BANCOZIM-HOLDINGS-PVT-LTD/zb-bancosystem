<?php

namespace Tests\Unit\Services\PDF;

use App\Models\ApplicationState;
use App\Services\PDF\PDFTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PDFTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private PDFTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PDFTemplateService;
    }

    public function test_determines_correct_template_for_ssb_employer()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'employer' => 'goz-ssb',
                'hasAccount' => false,
            ],
        ]);

        $template = $this->service->determineTemplate($applicationState);

        $this->assertEquals('forms.ssb_form_pdf', $template);
    }

    public function test_determines_correct_template_for_entrepreneur()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'employer' => 'entrepreneur',
                'hasAccount' => false,
            ],
        ]);

        $template = $this->service->determineTemplate($applicationState);

        $this->assertEquals('forms.sme_account_opening_pdf', $template);
    }

    public function test_determines_correct_template_for_account_holder()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'employer' => 'large-corporate',
                'hasAccount' => true,
            ],
        ]);

        $template = $this->service->determineTemplate($applicationState);

        $this->assertEquals('forms.account_holders_pdf', $template);
    }

    public function test_determines_correct_template_for_new_account()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'employer' => 'large-corporate',
                'hasAccount' => false,
            ],
        ]);

        $template = $this->service->determineTemplate($applicationState);

        $this->assertEquals('forms.zb_account_opening_pdf', $template);
    }

    public function test_prepares_template_data_correctly()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'emailAddress' => 'john.doe@example.com',
                    'loanAmount' => 50000,
                ],
                'employer' => 'large-corporate',
                'hasAccount' => true,
            ],
        ]);

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertArrayHasKey('firstName', $data);
        $this->assertArrayHasKey('lastName', $data);
        $this->assertArrayHasKey('emailAddress', $data);
        $this->assertArrayHasKey('applicationDate', $data);
        $this->assertArrayHasKey('applicationNumber', $data);
        $this->assertEquals('John', $data['firstName']);
        $this->assertEquals('Doe', $data['lastName']);
        $this->assertEquals('john.doe@example.com', $data['emailAddress']);
    }

    public function test_formats_currency_fields_correctly()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'formResponses' => [
                    'loanAmount' => 50000,
                    'monthlyPayment' => 1500.50,
                    'netSalary' => 3000,
                ],
            ],
        ]);

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertEquals('50,000.00', $data['loanAmount']);
        $this->assertEquals('1,500.50', $data['monthlyPayment']);
        $this->assertEquals('3,000.00', $data['netSalary']);
    }

    public function test_formats_date_fields_correctly()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'formResponses' => [
                    'dateOfBirth' => '1990-05-15',
                    'employmentStartDate' => '2020-01-01',
                ],
            ],
        ]);

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertEquals('15/05/1990', $data['dateOfBirth']);
        $this->assertEquals('01/01/2020', $data['employmentStartDate']);
    }

    public function test_handles_missing_form_data_gracefully()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [],
        ]);

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('applicationDate', $data);
        $this->assertArrayHasKey('applicationNumber', $data);
    }

    public function test_generates_unique_application_numbers()
    {
        $applicationState1 = ApplicationState::factory()->create();
        $applicationState2 = ApplicationState::factory()->create();

        $data1 = $this->service->prepareTemplateData($applicationState1);
        $data2 = $this->service->prepareTemplateData($applicationState2);

        $this->assertNotEquals($data1['applicationNumber'], $data2['applicationNumber']);
        $this->assertStringStartsWith('ZB', $data1['applicationNumber']);
        $this->assertStringStartsWith('ZB', $data2['applicationNumber']);
    }

    public function test_includes_employer_information()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'employer' => 'goz-ssb',
                'employerCategory' => 'government',
            ],
        ]);

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertArrayHasKey('employerInfo', $data);
        $this->assertEquals('goz-ssb', $data['employerInfo']['code']);
        $this->assertEquals('government', $data['employerInfo']['category']);
        $this->assertNotEmpty($data['employerInfo']['name']);
        $this->assertNotEmpty($data['employerInfo']['type']);
    }

    public function test_calculates_credit_facility_details()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'formResponses' => [
                    'loanAmount' => 100000,
                    'loanTenure' => 24,
                    'interestRate' => 12,
                ],
            ],
        ]);

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertArrayHasKey('creditFacility', $data);
        $this->assertEquals('24', $data['creditFacility']['term']);
        $this->assertEquals('months', $data['creditFacility']['termUnit']);
        $this->assertEquals('12.00%', $data['creditFacility']['interestRate']);
        $this->assertNotEmpty($data['creditFacility']['totalInterest']);
        $this->assertNotEmpty($data['creditFacility']['totalRepayment']);
    }

    public function test_processes_document_data()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'documents' => [
                    'selfie' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ...',
                    'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...',
                    'uploadedDocuments' => [
                        'national_id' => [
                            [
                                'name' => 'id_front.jpg',
                                'path' => 'documents/id_front.jpg',
                                'type' => 'image/jpeg',
                                'size' => 1024000,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertTrue($data['hasDocuments']);
        $this->assertNotEmpty($data['selfieImage']);
        $this->assertNotEmpty($data['signatureImage']);
        $this->assertArrayHasKey('documentsByType', $data);
        $this->assertArrayHasKey('national_id', $data['documentsByType']);
    }

    public function test_validates_required_template_data()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
            ],
        ]);

        $isValid = $this->service->validateTemplateData($applicationState);

        $this->assertTrue($isValid);
    }

    public function test_validation_fails_for_missing_required_data()
    {
        $applicationState = ApplicationState::factory()->create([
            'form_data' => [],
        ]);

        $isValid = $this->service->validateTemplateData($applicationState);

        $this->assertFalse($isValid);
    }

    public function test_gets_supported_templates()
    {
        $templates = $this->service->getSupportedTemplates();

        $this->assertIsArray($templates);
        $this->assertArrayHasKey('forms.ssb_form_pdf', $templates);
        $this->assertArrayHasKey('forms.sme_account_opening_pdf', $templates);
        $this->assertArrayHasKey('forms.zb_account_opening_pdf', $templates);
        $this->assertArrayHasKey('forms.account_holders_pdf', $templates);
    }

    public function test_template_exists_check()
    {
        $this->assertTrue($this->service->templateExists('forms.ssb_form_pdf'));
        $this->assertFalse($this->service->templateExists('forms.nonexistent_template'));
    }
}
