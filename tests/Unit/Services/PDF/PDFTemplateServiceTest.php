<?php

namespace Tests\Unit\Services\PDF;

use App\Models\ApplicationState;
use App\Services\PDF\PDFTemplateService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PDFTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private PDFTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PDFTemplateService();
    }

    public function test_determines_correct_template_for_ssb_employer()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'session-ssb',
            'user_identifier' => 'user-ssb',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'responsibleMinistry' => 'Ministry of Education',
            ]
        ]);

        $template = $this->service->detectFormType($applicationState);

        $this->assertEquals('forms.ssb_form_pdf', $template);
    }

    public function test_determines_correct_template_for_entrepreneur()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'session-sme',
            'user_identifier' => 'user-sme',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'businessName' => 'Test SME',
            ]
        ]);

        $template = $this->service->detectFormType($applicationState);

        $this->assertEquals('forms.sme_business_pdf', $template);
    }

    public function test_determines_correct_template_for_account_holder()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'session-ah',
            'user_identifier' => 'user-ah',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'hasAccount' => true,
            ]
        ]);

        $template = $this->service->detectFormType($applicationState);

        $this->assertEquals('forms.account_holders_pdf', $template);
    }

    public function test_determines_correct_template_for_new_account()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'session-new',
            'user_identifier' => 'user-new',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'wantsAccount' => true,
            ]
        ]);

        $template = $this->service->detectFormType($applicationState);

        $this->assertEquals('forms.zb_account_opening_pdf', $template);
    }

    public function test_prepares_template_data_correctly()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'session-data',
            'user_identifier' => 'user-data',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'emailAddress' => 'john.doe@example.com',
                ],
            ]
        ]);
        $applicationState->created_at = now();

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertArrayHasKey('firstName', $data);
        $this->assertArrayHasKey('lastName', $data);
        $this->assertArrayHasKey('emailAddress', $data);
        $this->assertArrayHasKey('submissionDate', $data);
        $this->assertEquals('John', $data['firstName']);
        $this->assertEquals('Doe', $data['lastName']);
    }

    public function test_formats_currency_fields_correctly()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'session-currency',
            'user_identifier' => 'user-currency',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => [
                    'salary' => 3000,
                    'otherIncome' => 1500.50,
                ],
            ]
        ]);
        $applicationState->created_at = now();

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertEquals('3,000.00', $data['salary']);
        $this->assertEquals('1,500.50', $data['otherIncome']);
    }

    public function test_formats_date_fields_correctly()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'session-date',
            'user_identifier' => 'user-date',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => [
                'formResponses' => [
                    'dateOfBirth' => '1990-05-15',
                    'employmentStartDate' => '2020-01-01',
                ],
            ]
        ]);
        $applicationState->created_at = now();

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertEquals('15/05/1990', $data['dateOfBirth']);
        $this->assertEquals('01/01/2020', $data['employmentStartDate']);
    }

    public function test_handles_missing_form_data_gracefully()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'session-missing',
            'user_identifier' => 'user-missing',
            'channel' => 'web',
            'current_step' => 'completed',
            'form_data' => []
        ]);
        $applicationState->created_at = now();

        $data = $this->service->prepareTemplateData($applicationState);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('submissionDate', $data);
    }
}
