<?php

namespace Tests\Feature;

use Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    /**
     * Test that the SSB form PDF can be downloaded.
     */
    public function test_ssb_form_pdf_download(): void
    {
        $response = $this->get('/download-ssb-form');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=ssb_loan_application_form.pdf');
    }

    /**
     * Test that the Account Holders form PDF can be downloaded.
     */
    public function test_account_holders_form_pdf_download(): void
    {
        $response = $this->get('/download-account-holders-form');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=Account_Holders_Application_Form.pdf');
    }

    /**
     * Test that the ZB Account Opening form PDF can be downloaded.
     */
    public function test_zb_account_form_pdf_download(): void
    {
        $response = $this->get('/download-zb-account-form');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=ZB_Account_Opening_Form.pdf');
    }

    /**
     * Test that logo images exist and are accessible for PDF generation.
     */
    public function test_logo_images_exist(): void
    {
        $this->assertFileExists(public_path('assets/images/qupa.png'));
        $this->assertFileExists(public_path('assets/images/bancozim.png'));
        $this->assertFileExists(public_path('assets/images/zb_logo.png'));
    }

    /**
     * Test that the home page loads successfully.
     */
    public function test_home_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // The page should contain the Inertia app div
        $response->assertSee('id="app"', false);
    }
}
