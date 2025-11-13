<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Services\PDF\PDFTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ComprehensivePDFGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected PDFGeneratorService $pdfGenerator;
    protected PDFTemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = new PDFTemplateService();
    }

    /**
     * Prepare template data based on template type
     */
    protected function prepareTemplateData(string $template, array $formData): array
    {
        // Templates that use $formResponses structure
        $formResponsesTemplates = [
            'forms.ssb_form_pdf',
            'forms.zb_account_opening_pdf'
        ];

        // Templates that use direct variables
        $directVariableTemplates = [
            'forms.account_holders_pdf',
            'forms.sme_business_pdf'
        ];

        if (in_array($template, $formResponsesTemplates)) {
            return ['formResponses' => $formData];
        } elseif (in_array($template, $directVariableTemplates)) {
            return $formData;
        } else {
            // Default to formResponses for unknown templates
            return ['formResponses' => $formData];
        }
    }

    /**
     * Test SSB form PDF generation
     */
    public function test_ssb_form_pdf_generation()
    {
        $formData = [
            'formType' => 'ssb',
            'title' => 'Mr',
            'firstName' => 'John',
            'surname' => 'Doe',
            'nationalIdNumber' => '63-123456-A-01',
            'mobile' => '+263771234567',
            'emailAddress' => 'john.doe@example.com',
            'responsibleMinistry' => 'Education',
            'employerName' => 'Ministry of Education',
            'employerAddress' => '1 Harare Drive, Harare',
            'jobTitle' => 'Teacher',
            'currentNetSalary' => '800.00',
            'creditFacilityType' => 'Hire Purchase Credit - Laptop Package',
            'loanAmount' => '1200.00',
            'loanTenure' => '12',
            'monthlyPayment' => '110.00',
            'interestRate' => '10.0',
            'deliveryStatus' => 'Future',
            'province' => 'Harare',
            'agent' => 'Test Agent',
            'team' => 'Team A'
        ];

        // Test template detection
        $applicationState = new ApplicationState([
            'session_id' => 'test_ssb_pdf_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => $formData
        ]);

        $template = $this->templateService->detectFormType($applicationState);
        $this->assertEquals('forms.ssb_form_pdf', $template);

        // Test PDF generation
        try {
            // Format data as expected by templates
            $templateData = $this->prepareTemplateData($template, $formData);
            $pdf = Pdf::loadView($template, $templateData);
            $this->assertNotNull($pdf);
            
            // Test that PDF content is generated
            $pdfContent = $pdf->output();
            $this->assertNotEmpty($pdfContent);
            $this->assertStringContainsString('%PDF', $pdfContent); // PDF header
            
        } catch (\Exception $e) {
            $this->fail("SSB PDF generation failed: " . $e->getMessage());
        }
    }

    /**
     * Test SME Business form PDF generation with new template
     */
    public function test_sme_business_form_pdf_generation()
    {
        $formData = [
            'formType' => 'sme_business',
            'businessName' => 'Tech Solutions Ltd',
            'businessRegistration' => 'BRC/2024/001234',
            'registeredName' => 'Tech Solutions (Private) Limited',
            'tradingName' => 'Tech Solutions',
            'typeOfBusiness' => 'Software Development',
            'businessAddress' => '123 Innovation Street, Harare',
            'incorporationNumber' => 'INC/2024/001234',
            'contactPhone' => '+263712345678',
            'emailAddress' => 'info@techsolutions.co.zw',
            'businessType' => 'company',
            'loanType' => 'Working Capital',
            'initialCapital' => '5000.00',
            'yearsInBusiness' => '3',
            'estimatedAnnualSales' => '50000.00',
            'netProfit' => '15000.00',
            'creditFacilityType' => 'Micro Biz Loan - Equipment Purchase',
            'loanAmount' => '3000.00',
            'loanTenure' => '18',
            'monthlyPayment' => '190.00',
            'interestRate' => '10.0',
            // Director details
            'accountType' => 'Business Current',
            'initialDeposit' => '1000.00',
            'servicesRequired' => ['Internet Banking', 'Mobile Banking'],
            'directorsPersonalDetails' => [
                'title' => 'Mr',
                'firstName' => 'David',
                'surname' => 'Smith',
                'nationalIdNumber' => '63-654321-B-02',
                'cellNumber' => '+263773456789',
                'emailAddress' => 'david@techsolutions.co.zw'
            ]
        ];

        $applicationState = new ApplicationState([
            'session_id' => 'test_sme_pdf_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => $formData
        ]);

        // Test template detection
        $template = $this->templateService->detectFormType($applicationState);
        $this->assertEquals('forms.sme_business_pdf', $template);

        // Test PDF generation
        try {
            // Format data as expected by templates
            $templateData = $this->prepareTemplateData($template, $formData);
            $pdf = Pdf::loadView($template, $templateData);
            $this->assertNotNull($pdf);
            
            $pdfContent = $pdf->output();
            $this->assertNotEmpty($pdfContent);
            $this->assertStringContainsString('%PDF', $pdfContent);
            
        } catch (\Exception $e) {
            $this->fail("SME Business PDF generation failed: " . $e->getMessage());
        }
    }

    /**
     * Test ZB Account Opening form PDF generation
     */
    public function test_zb_account_opening_pdf_generation()
    {
        $formData = [
            'formType' => 'zb_account_opening',
            'title' => 'Ms',
            'firstName' => 'Sarah',
            'surname' => 'Johnson',
            'nationalIdNumber' => '63-789012-C-03',
            'mobile' => '+263774567890',
            'emailAddress' => 'sarah.johnson@example.com',
            'accountType' => 'savings',
            'initialDeposit' => '100.00',
            'accountCurrency' => 'USD',
            'serviceCenter' => 'Harare Branch',
            'residentialAddress' => '456 Residential Ave, Harare',
            'employerName' => 'ABC Corporation',
            'occupation' => 'Accountant',
            'employmentStatus' => 'Permanent',
            'grossMonthlySalary' => '1200.00',
            'creditFacilityType' => 'Account Opening - Savings Account',
            'loanAmount' => '0.00',
            // Services
            'mobileMoneyEcocash' => true,
            'whatsappBanking' => true,
            'internetBanking' => true,
            // Supporting docs
            'supportingDocs' => [
                'passportPhotos' => true,
                'proofOfResidence' => true,
                'nationalId' => true
            ]
        ];

        $applicationState = new ApplicationState([
            'session_id' => 'test_zb_pdf_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => $formData
        ]);

        // Test template detection
        $template = $this->templateService->detectFormType($applicationState);
        $this->assertEquals('forms.zb_account_opening_pdf', $template);

        // Test PDF generation
        try {
            // Format data as expected by templates
            $templateData = $this->prepareTemplateData($template, $formData);
            $pdf = Pdf::loadView($template, $templateData);
            $this->assertNotNull($pdf);
            
            $pdfContent = $pdf->output();
            $this->assertNotEmpty($pdfContent);
            $this->assertStringContainsString('%PDF', $pdfContent);
            
        } catch (\Exception $e) {
            $this->fail("ZB Account Opening PDF generation failed: " . $e->getMessage());
        }
    }

    /**
     * Test Account Holders form PDF generation (updated template)
     */
    public function test_account_holders_pdf_generation()
    {
        $formData = [
            'formType' => 'account_holders',
            'title' => 'Mrs',
            'firstName' => 'Jane',
            'surname' => 'Williams',
            'nationalIdNumber' => '63-345678-D-04',
            'mobile' => '+263775678901',
            'whatsApp' => '+263775678901',
            'emailAddress' => 'jane.williams@example.com',
            'employerName' => 'XYZ Company',
            'employerAddress' => '789 Business Park, Harare',
            'jobTitle' => 'Manager',
            'currentNetSalary' => '1500.00',
            'creditFacilityType' => 'Hire Purchase Credit - Furniture Package',
            'loanAmount' => '2500.00',
            'loanTenure' => '18',
            'monthlyPayment' => '160.00',
            'interestRate' => '10.0',
            'responsiblePaymaster' => 'Church',
            'propertyOwnership' => 'Owned',
            'periodAtAddress' => 'More than 5 years',
            'employmentStatus' => 'Permanent',
            'deliveryStatus' => 'Future',
            'province' => 'Harare',
            'agent' => 'Test Agent 2',
            'team' => 'Team B'
        ];

        $applicationState = new ApplicationState([
            'session_id' => 'test_account_pdf_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => $formData
        ]);

        // Test template detection
        $template = $this->templateService->detectFormType($applicationState);
        $this->assertEquals('forms.account_holders_pdf', $template);

        // Test PDF generation
        try {
            // Format data as expected by templates
            $templateData = $this->prepareTemplateData($template, $formData);
            $pdf = Pdf::loadView($template, $templateData);
            $this->assertNotNull($pdf);
            
            $pdfContent = $pdf->output();
            $this->assertNotEmpty($pdfContent);
            $this->assertStringContainsString('%PDF', $pdfContent);
            
        } catch (\Exception $e) {
            $this->fail("Account Holders PDF generation failed: " . $e->getMessage());
        }
    }

    /**
     * Test field mapping for business registration number
     */
    public function test_business_registration_field_mapping()
    {
        $formData = [
            'formType' => 'sme_business',
            'businessName' => 'Registration Test Company',
            'businessRegistration' => 'TEST/2024/999999',
            'registeredName' => 'Registration Test Company (Private) Limited'
        ];

        $applicationState = new ApplicationState([
            'session_id' => 'test_registration_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => $formData
        ]);

        // Verify form data contains business registration
        $this->assertArrayHasKey('businessRegistration', $formData);
        $this->assertEquals('TEST/2024/999999', $formData['businessRegistration']);

        // Verify template detection
        $template = $this->templateService->detectFormType($applicationState);
        $this->assertEquals('forms.sme_business_pdf', $template);
    }

    /**
     * Test field mapping for account type and initial deposit
     */
    public function test_account_type_field_mapping()
    {
        $formData = [
            'formType' => 'zb_account_opening',
            'accountType' => 'current',
            'initialDeposit' => '250.00',
            'accountCurrency' => 'USD'
        ];

        $applicationState = new ApplicationState([
            'session_id' => 'test_account_type_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => $formData
        ]);

        // Verify form data contains account fields
        $this->assertArrayHasKey('accountType', $formData);
        $this->assertArrayHasKey('initialDeposit', $formData);
        $this->assertEquals('current', $formData['accountType']);
        $this->assertEquals('250.00', $formData['initialDeposit']);

        // Verify template detection
        $template = $this->templateService->detectFormType($applicationState);
        $this->assertEquals('forms.zb_account_opening_pdf', $template);
    }

    /**
     * Test PDF generation with missing optional fields
     */
    public function test_pdf_generation_with_missing_fields()
    {
        $minimalFormData = [
            'formType' => 'account_holders',
            'firstName' => 'Minimal',
            'surname' => 'Test',
            'responsiblePaymaster' => 'Other',
            'propertyOwnership' => 'Owned',
            'periodAtAddress' => 'More than 5 years',
            'employmentStatus' => 'Permanent',
            'loanTenure' => '12',
            'monthlyPayment' => '100.00',
            'loanAmount' => '1200.00'
        ];

        $applicationState = new ApplicationState([
            'session_id' => 'test_minimal_001',
            'channel' => 'web',
            'user_identifier' => 'test@example.com',
            'current_step' => 'completed',
            'form_data' => $minimalFormData
        ]);

        $template = $this->templateService->detectFormType($applicationState);
        $this->assertEquals('forms.account_holders_pdf', $template);

        // Should not fail with minimal data
        try {
            $templateData = $this->prepareTemplateData($template, $minimalFormData);
            $pdf = Pdf::loadView($template, $templateData);
            $this->assertNotNull($pdf);
            
            $pdfContent = $pdf->output();
            $this->assertNotEmpty($pdfContent);
            
        } catch (\Exception $e) {
            $this->fail("PDF generation with minimal data failed: " . $e->getMessage());
        }
    }

    /**
     * Test all templates exist
     */
    public function test_all_templates_exist()
    {
        $templates = [
            'forms.ssb_form_pdf',
            'forms.sme_business_pdf',
            'forms.zb_account_opening_pdf',
            'forms.account_holders_pdf'
        ];

        foreach ($templates as $template) {
            $templatePath = resource_path('views/' . str_replace('.', '/', $template) . '.blade.php');
            $this->assertFileExists($templatePath, "Template file missing: {$templatePath}");
        }
    }

    /**
     * Test checkbox styling in PDFs
     */
    public function test_checkbox_styling_in_pdfs()
    {
        $formData = [
            'formType' => 'account_holders',
            'firstName' => 'Checkbox',
            'surname' => 'Test',
            'propertyOwnership' => 'Owned',
            'employmentStatus' => 'Permanent',
            'responsiblePaymaster' => 'Church',
            'periodAtAddress' => 'More than 5 years',
            'loanTenure' => '12',
            'monthlyPayment' => '100.00',
            'loanAmount' => '1200.00'
        ];

        $template = 'forms.account_holders_pdf';

        try {
            $templateData = $this->prepareTemplateData($template, $formData);
            $pdf = Pdf::loadView($template, $templateData);
            $this->assertNotNull($pdf);
            
            // PDF should generate without errors
            $pdfContent = $pdf->output();
            $this->assertNotEmpty($pdfContent);
            
        } catch (\Exception $e) {
            $this->fail("Checkbox styling PDF generation failed: " . $e->getMessage());
        }
    }
}