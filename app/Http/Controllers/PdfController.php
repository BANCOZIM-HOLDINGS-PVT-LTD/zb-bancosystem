<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PdfController extends Controller
{
    /**
     * Generate and download the SSB form PDF
     */
    public function downloadSsbForm(Request $request)
    {
        // Sample data - restructured to match the template's expected format
        $formData = [
            'formResponses' => [
                // Personal Details
                'title' => 'Mr',
                'firstName' => 'Tapiwanashe',
                'surname' => 'Maposhere',
                'gender' => 'Male',
                'dateOfBirth' => '01/01/1990',
                'maritalStatus' => 'Married',
                'nationality' => 'Zimbabwean',
                'nationalIdNumber' => '63-123456A63',
                'idNumber' => '63-123456A63',

                // Contact Details
                'mobile' => '+263 77 123 4567',
                'cellNumber' => '+263 77 123 4567',
                'whatsApp' => '+263 77 123 4567',
                'emailAddress' => 'tapiwanashe@example.com',

                // Employment Details
                'responsibleMinistry' => 'Education',
                'employerName' => 'Ministry of Education',
                'employerAddress' => '1 Causeway, Harare',
                'residentialAddress' => '123 Main Street, Harare',
                'permanentAddress' => '123 Main Street, Harare',
                'propertyOwnership' => 'Owned',
                'periodAtAddress' => '5',
                'employmentStatus' => 'Permanent',
                'jobTitle' => 'Teacher',
                'dateOfEmployment' => '01/01/2020',
                'employmentNumber' => 'EMP001',
                'employeeNumber' => 'EMP001',
                'headOfInstitution' => 'Mr. Principal',
                'headOfInstitutionCell' => '+263 77 234 5678',
                'currentNetSalary' => '500.00',

                // Banking Details
                'bankName' => 'ZB Bank',
                'branch' => 'Harare',
                'accountNumber' => '1234567890',

                // Loan Details
                'loanAmount' => '1000.00',
                'loanTenure' => '12',
                'loanPurpose' => 'Purchase of household items',
                'purposeAsset' => 'Kitchen appliances and furniture',

                // Spouse/Next of Kin
                'spouseDetails' => [
                    [
                        'fullName' => 'Jane Doe',
                        'relationship' => 'Spouse',
                        'phoneNumber' => '+263 77 987 6543',
                        'residentialAddress' => '123 Main Street, Harare'
                    ],
                    [
                        'fullName' => 'John Smith',
                        'relationship' => 'Brother',
                        'phoneNumber' => '+263 77 555 1234',
                        'residentialAddress' => '456 Second Street, Harare'
                    ]
                ],

                // Additional fields
                'deliveryStatus' => 'Future',
                'province' => 'Harare',
                'agent' => 'John Doe',
                'team' => 'Team A',
                'paypoint' => '1234',
                'payrollNumber' => '5678',
                'checkLetter' => 'A',
                'loanStartDate' => date('Y-m-d'),

                // Signature field (can be base64 or file path)
                'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ],
            'monthlyPayment' => '100.00',

            // Documents array (alternative location for signature)
            'documents' => [
                'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'selfie' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'uploadedDocuments' => [
                    'payslip' => [
                        [
                            'path' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                            'name' => 'payslip.png'
                        ]
                    ],
                    'nationalId' => [
                        [
                            'path' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                            'name' => 'national_id.png'
                        ]
                    ]
                ]
            ],

            // Signature image (another alternative location)
            'signatureImage' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'selfieImage' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        ];

        // Generate PDF with optimized settings
        $pdf = Pdf::loadView('forms.ssb_form_pdf', $formData);
        $pdf->setPaper('A4', 'portrait');

        // Set DomPDF options for better performance
        $pdf->setOptions([
            'isRemoteEnabled' => false,  // Disable remote file loading for speed
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => false,  // Disable font subsetting for speed
            'debugKeepTemp' => false,
            'debugCss' => false,
            'debugLayout' => false,
            'debugLayoutLines' => false,
            'debugLayoutBlocks' => false,
            'debugLayoutInline' => false,
            'debugLayoutPaddingBox' => false,
        ]);

        // Return the PDF as a download
        return $pdf->download('ssb_loan_application_form.pdf');
    }

    /**
     * Generate and download the ZB Account Opening form PDF
     */
    public function downloadZbAccountForm(Request $request)
    {
        // Generate PDF from the Blade view
        $pdf = Pdf::loadView('forms.zb_account_opening_pdf');
        $pdf->setPaper('A4', 'portrait');

        // Return the PDF as a download
        return $pdf->download('ZB_Account_Opening_Form.pdf');
    }

    /**
     * Generate and download the Account Holders form PDF
     */
    public function downloadAccountHoldersForm(Request $request)
    {
        // Generate PDF from the Blade view
        $pdf = Pdf::loadView('forms.account_holders_pdf');
        $pdf->setPaper('A4', 'portrait');

        // Return the PDF as a download
        return $pdf->download('Account_Holders_Application_Form.pdf');
    }

    /**
     * Generate and download the SME Account Opening form PDF
     */
    public function downloadSmeAccountOpeningForm(Request $request)
    {
        // Generate PDF from the Blade view
        $pdf = Pdf::loadView('forms.sme_account_opening_pdf');
        $pdf->setPaper('A4', 'portrait');

        // Return the PDF as a download
        return $pdf->download('SME_Account_Opening_Form.pdf');
    }
}
