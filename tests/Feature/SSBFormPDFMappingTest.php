<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class SSBFormPDFMappingTest extends TestCase
{
    /**
     * Test SSB form PDF generation with comprehensive data mapping
     */
    public function test_ssb_form_pdf_data_mapping(): void
    {
        // Comprehensive test data covering all SSB form fields
        $formData = [
            'formResponses' => [
                // Personal Details
                'title' => 'Mr',
                'firstName' => 'John',
                'surname' => 'Doe',
                'lastName' => 'Doe', // Alternative field name
                'gender' => 'Male',
                'dateOfBirth' => '1985-05-15',
                'maritalStatus' => 'Married',
                'nationality' => 'Zimbabwean',
                'idNumber' => '63-123456-A-01',
                'nationalIdNumber' => '63-123456-A-01',

                // Contact Details
                'cellNumber' => '+263771234567',
                'mobile' => '+263771234567',
                'whatsApp' => '+263771234567',
                'emailAddress' => 'john.doe@example.com',

                // Employment Details
                'responsibleMinistry' => 'Education',
                'employerName' => 'Ministry of Education',
                'employerAddress' => '1 Harare Drive, Harare',
                'residentialAddress' => '123 Main Street, Harare',
                'permanentAddress' => '123 Main Street, Harare',
                'propertyOwnership' => 'Owned',
                'periodAtAddress' => '5',
                'employmentStatus' => 'Permanent',
                'jobTitle' => 'Senior Teacher',
                'dateOfEmployment' => '2015-01-10',
                'employmentNumber' => '12345678',
                'employeeNumber' => '12345678',
                'headOfInstitution' => 'Mr. Principal',
                'responsiblePaymaster' => 'Mr. Principal',
                'headOfInstitutionCell' => '+263772345678',
                'currentNetSalary' => '800.00',
                'netSalary' => '800.00',

                // Spouse and Next of Kin
                'spouseDetails' => [
                    [
                        'fullName' => 'Jane Doe',
                        'relationship' => 'Spouse',
                        'phoneNumber' => '+263773456789',
                        'residentialAddress' => '123 Main Street, Harare',
                    ],
                    [
                        'fullName' => 'Mike Doe',
                        'relationship' => 'Brother',
                        'phoneNumber' => '+263774567890',
                        'residentialAddress' => '456 Second Ave, Bulawayo',
                    ],
                ],

                // Banking Details
                'bankName' => 'ZB Bank',
                'branch' => 'Harare Branch',
                'bankBranch' => 'Harare Branch',
                'accountNumber' => '1234567890',

                // Loan Details
                'loanAmount' => '1200.00',
                'amount' => '1200.00',
                'finalPrice' => '1200.00',
                'loanTenure' => '12',
                'term' => '12',
                'creditFacilityType' => 'Hire Purchase Credit - Laptop Package',
                'business' => 'Hire Purchase Credit - Laptop Package',
                'loanPurpose' => 'Purchase of Laptop',

                // Other Details
                'deliveryStatus' => 'Future',
                'province' => 'Harare',
                'agent' => 'Test Agent',
                'team' => 'Team A',
                'paypoint' => '1234',
                'payrollNumber' => '5678',
                'checkLetter' => 'A',
                'loanStartDate' => '2025-11-01',
            ],
            'monthlyPayment' => '110.00',
            'interestRate' => '10.0',
        ];

        // Test that the view can be rendered
        $view = View::make('forms.ssb_form_pdf', $formData);
        $html = $view->render();

        // Check that key data points are present in the rendered HTML
        $this->assertStringContainsString('John', $html, 'First name should be in the PDF');
        $this->assertStringContainsString('Doe', $html, 'Surname should be in the PDF');
        $this->assertStringContainsString('63-123456-A-01', $html, 'ID number should be in the PDF');
        $this->assertStringContainsString('771234567', $html, 'Phone number should be in the PDF');
        $this->assertStringContainsString('john.doe@example.com', $html, 'Email should be in the PDF');
        $this->assertStringContainsString('Ministry of Education', $html, 'Employer should be in the PDF');
        $this->assertStringContainsString('Senior Teacher', $html, 'Job title should be in the PDF');
        $this->assertStringContainsString('800.00', $html, 'Salary should be in the PDF');
        $this->assertStringContainsString('1200.00', $html, 'Loan amount should be in the PDF');
        $this->assertStringContainsString('110.00', $html, 'Monthly payment should be in the PDF');
        $this->assertStringContainsString('Jane Doe', $html, 'Spouse name should be in the PDF');
        $this->assertStringContainsString('ZB Bank', $html, 'Bank name should be in the PDF');
        $this->assertStringContainsString('Laptop', $html, 'Loan purpose should be in the PDF');

        // Test PDF generation
        try {
            $pdf = Pdf::loadView('forms.ssb_form_pdf', $formData);
            $this->assertNotNull($pdf, 'PDF object should be created');

            $pdfContent = $pdf->output();
            $this->assertNotEmpty($pdfContent, 'PDF content should not be empty');
            $this->assertStringContainsString('%PDF', $pdfContent, 'Should be a valid PDF file');

            // Check PDF size is reasonable (should be at least 10KB for a 4-page form with images)
            $this->assertGreaterThan(10000, strlen($pdfContent), 'PDF should be at least 10KB in size');

        } catch (\Exception $e) {
            $this->fail('SSB PDF generation failed: '.$e->getMessage());
        }
    }

    /**
     * Test SSB form PDF with minimal data
     */
    public function test_ssb_form_pdf_with_minimal_data(): void
    {
        // Minimal data to test defaults and empty field handling
        $formData = [
            'formResponses' => [
                'firstName' => 'Jane',
                'surname' => 'Smith',
                'idNumber' => '63-987654-B-02',
            ],
            'monthlyPayment' => '100.00',
        ];

        try {
            $view = View::make('forms.ssb_form_pdf', $formData);
            $html = $view->render();

            // Check that minimal data is present
            $this->assertStringContainsString('Jane', $html);
            $this->assertStringContainsString('Smith', $html);
            $this->assertStringContainsString('63-987654-B-02', $html);

            // Check that defaults are applied
            $this->assertStringContainsString('Zimbabwean', $html, 'Default nationality should be applied');

            // Should not throw errors with missing fields
            $pdf = Pdf::loadView('forms.ssb_form_pdf', $formData);
            $pdfContent = $pdf->output();
            $this->assertNotEmpty($pdfContent);

        } catch (\Exception $e) {
            $this->fail('SSB PDF with minimal data failed: '.$e->getMessage());
        }
    }

    /**
     * Test that logo images are accessible and included in PDF
     */
    public function test_ssb_form_pdf_logo_rendering(): void
    {
        // Check that logo files exist
        $this->assertFileExists(public_path('assets/images/qupa.png'), 'Qupa logo should exist');
        $this->assertFileExists(public_path('assets/images/bancozim.png'), 'BancoZim logo should exist');

        // Check file sizes (logos should be reasonable size)
        $qupaSize = filesize(public_path('assets/images/qupa.png'));
        $bancozimSize = filesize(public_path('assets/images/bancozim.png'));

        $this->assertGreaterThan(100, $qupaSize, 'Qupa logo should be at least 100 bytes');
        $this->assertGreaterThan(100, $bancozimSize, 'BancoZim logo should be at least 100 bytes');

        // Test that logos are referenced in the HTML
        $formData = [
            'formResponses' => [
                'firstName' => 'Test',
                'surname' => 'User',
            ],
        ];

        $view = View::make('forms.ssb_form_pdf', $formData);
        $html = $view->render();

        // Check that image tags are present (using asset() helper)
        $this->assertStringContainsString('qupa.png', $html, 'Qupa logo should be referenced');
        $this->assertStringContainsString('bancozim.png', $html, 'BancoZim logo should be referenced');
        $this->assertStringContainsString('<img', $html, 'Should contain image tags');
    }

    /**
     * Test helper functions for data retrieval
     */
    public function test_ssb_form_helper_functions(): void
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'Test',
                'surname' => 'Helper',
                'mobile' => '+263771111111',
                'loanTenure' => '6',
                'arrayField' => ['should', 'be', 'ignored'],
            ],
        ];

        $view = View::make('forms.ssb_form_pdf', $formData);
        $html = $view->render();

        // Verify that array fields are handled safely (should not cause errors)
        $this->assertStringNotContainsString('Array', $html, 'Array values should not be printed as "Array"');

        // Verify proper data extraction
        $this->assertStringContainsString('Test', $html);
        $this->assertStringContainsString('Helper', $html);
        $this->assertStringContainsString('771111111', $html);
    }

    /**
     * Test all four pages of SSB form are generated
     */
    public function test_ssb_form_all_pages_generated(): void
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'Multi',
                'surname' => 'Page',
                'idNumber' => '63-111111-C-03',
            ],
        ];

        $view = View::make('forms.ssb_form_pdf', $formData);
        $html = $view->render();

        // Check for page-specific content
        $this->assertStringContainsString('SSB LOAN APPLICATION AND CONTRACT FORM', $html, 'Page 1 header should be present');
        $this->assertStringContainsString('EARLY CONTRACT TERMINATION', $html, 'Page 2 content should be present');
        $this->assertStringContainsString('DEDUCTION ORDER FORM - TY 30', $html, 'Page 3 content should be present');
        $this->assertStringContainsString('PRODUCT ORDER FORM (P.O.F)', $html, 'Page 4 content should be present');

        // Check for page numbers
        $this->assertStringContainsString('Page 1 of 4', $html);
        $this->assertStringContainsString('Page 2 of 4', $html);
        $this->assertStringContainsString('Page 3 of 4', $html);
        $this->assertStringContainsString('Page 4 of 4', $html);
    }

    /**
     * Test SSB form sections are properly rendered
     */
    public function test_ssb_form_sections(): void
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'Section',
                'surname' => 'Test',
            ],
        ];

        $view = View::make('forms.ssb_form_pdf', $formData);
        $html = $view->render();

        // Check all major sections exist
        $this->assertStringContainsString('1. CUSTOMER PERSONAL DETAILS', $html);
        $this->assertStringContainsString('2. SPOUSE AND NEXT OF KIN DETAILS', $html);
        $this->assertStringContainsString('3. BANKING/MOBILE ACCOUNT DETAILS', $html);
        $this->assertStringContainsString('4. CREDIT FACILITY APPLICATION DETAILS', $html);
        $this->assertStringContainsString('DECLARATION', $html);
        $this->assertStringContainsString('FOR OFFICIAL USE ONLY', $html);
        $this->assertStringContainsString('KYC Checklist', $html);
    }

    /**
     * Test date formatting in SSB form
     */
    public function test_ssb_form_date_formatting(): void
    {
        $formData = [
            'formResponses' => [
                'firstName' => 'Date',
                'surname' => 'Test',
                'loanStartDate' => '2025-11-15',
                'loanTenure' => '12',
            ],
        ];

        $view = View::make('forms.ssb_form_pdf', $formData);
        $html = $view->render();

        // Check that current date is present
        $currentYear = date('Y');
        $this->assertStringContainsString($currentYear, $html, 'Current year should be in the PDF');
    }
}
