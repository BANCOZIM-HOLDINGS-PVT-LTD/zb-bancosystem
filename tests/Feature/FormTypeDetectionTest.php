<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Services\PDF\PDFTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FormTypeDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected PDFTemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = new PDFTemplateService();
    }

    /**
     * Test SSB form type detection
     */
    public function test_ssb_form_type_detection()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test_ssb_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'firstName' => 'John',
                'surname' => 'Doe',
                'responsibleMinistry' => 'Education',
                'employerName' => 'Ministry of Education',
                'creditFacilityType' => 'Hire Purchase Credit'
            ]
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.ssb_form_pdf', $detectedTemplate);
    }

    /**
     * Test SME business form type detection by business name
     */
    public function test_sme_business_form_type_detection_by_business_name()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'businessName' => 'Tech Solutions Ltd',
                'businessRegistration' => 'BRC/2024/001234',
                'registeredName' => 'Tech Solutions (Private) Limited',
                'typeOfBusiness' => 'Software Development'
            ]
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.sme_business_pdf', $detectedTemplate);
    }

    /**
     * Test SME business form type detection by registration number
     */
    public function test_sme_business_form_type_detection_by_registration()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'firstName' => 'Jane',
                'surname' => 'Smith',
                'businessRegistration' => 'BRC/2024/005678',
                'incorporationNumber' => 'INC/2024/005678'
            ]
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.sme_business_pdf', $detectedTemplate);
    }

    /**
     * Test ZB account opening form type detection
     */
    public function test_zb_account_opening_form_type_detection()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'firstName' => 'Mike',
                'surname' => 'Johnson',
                'accountType' => 'savings',
                'initialDeposit' => '100.00',
                'accountCurrency' => 'USD'
            ]
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.zb_account_opening_pdf', $detectedTemplate);
    }

    /**
     * Test account holders form type detection (default)
     */
    public function test_account_holders_form_type_detection_default()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'firstName' => 'Sarah',
                'surname' => 'Williams',
                'employerName' => 'ABC Corporation',
                'monthlyPayment' => '150.00',
                'loanTenure' => '12'
            ]
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.account_holders_pdf', $detectedTemplate);
    }

    /**
     * Test explicit form type from metadata
     */
    public function test_explicit_form_type_from_metadata()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'firstName' => 'David',
                'surname' => 'Brown',
                'formType' => 'sme_business',
                'businessName' => 'Brown Enterprises'
            ]
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.sme_business_pdf', $detectedTemplate);
    }

    /**
     * Test form ID mapping
     */
    public function test_form_id_mapping()
    {
        $testCases = [
            'ssb_account_opening_form.json' => 'forms.ssb_form_pdf',
            'smes_business_account_opening.json' => 'forms.sme_business_pdf',
            'individual_account_opening.json' => 'forms.zb_account_opening_pdf',
            'account_holder_loan_application.json' => 'forms.account_holders_pdf',
            'unknown_form.json' => 'forms.account_holders_pdf' // Default
        ];

        foreach ($testCases as $formId => $expectedTemplate) {
            $actualTemplate = $this->templateService->getTemplateForFormId($formId);
            $this->assertEquals($expectedTemplate, $actualTemplate, "Failed for form ID: {$formId}");
        }
    }

    /**
     * Test form type priority - explicit type should override detection
     */
    public function test_form_type_priority()
    {
        // Even though this has businessName, explicit formType should take precedence
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'formType' => 'ssb',
                'businessName' => 'Some Business', // This would normally trigger SME detection
                'responsibleMinistry' => 'Health'
            ]
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.ssb_form_pdf', $detectedTemplate);
    }

    /**
     * Test edge case with conflicting indicators
     */
    public function test_conflicting_form_indicators()
    {
        // Form data with both SSB and SME indicators
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => [
                'firstName' => 'Test',
                'surname' => 'User',
                'responsibleMinistry' => 'Education', // SSB indicator
                'businessName' => 'Test Business', // SME indicator
                'accountType' => 'savings' // ZB indicator
            ]
        ]);

        // Should pick the first match in priority order (SSB should win)
        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.ssb_form_pdf', $detectedTemplate);
    }

    /**
     * Test empty form data handling
     */
    public function test_empty_form_data_handling()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => []
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.account_holders_pdf', $detectedTemplate);
    }

    /**
     * Test null form data handling
     */
    public function test_null_form_data_handling()
    {
        $applicationState = new ApplicationState([
            'session_id' => 'test_' . uniqid(),
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => null
        ]);

        $detectedTemplate = $this->templateService->detectFormType($applicationState);

        $this->assertEquals('forms.account_holders_pdf', $detectedTemplate);
    }
}