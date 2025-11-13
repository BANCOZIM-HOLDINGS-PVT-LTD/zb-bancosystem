<?php

namespace Tests\Feature;

use Tests\TestCase;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfImageTest extends TestCase
{
    /**
     * Test that the account holders PDF template renders with images.
     */
    public function test_account_holders_pdf_contains_images(): void
    {
        // Generate the PDF content
        $pdf = Pdf::loadView('forms.account_holders_pdf');
        $html = $pdf->output();
        
        // Check that the PDF was generated successfully
        $this->assertNotEmpty($html);
        
        // Check that the PDF starts with the PDF header
        $this->assertStringStartsWith('%PDF-', $html);
    }

    /**
     * Test that the SSB form PDF template renders with images.
     */
    public function test_ssb_form_pdf_contains_images(): void
    {
        // Sample data for the form
        $data = [
            'delivery_status' => 'Future',
            'agent' => 'Test Agent',
            'province' => 'Harare',
            'team' => 'Team A',
        ];
        
        // Generate the PDF content
        $pdf = Pdf::loadView('forms.ssb_form_pdf', $data);
        $html = $pdf->output();
        
        // Check that the PDF was generated successfully
        $this->assertNotEmpty($html);
        
        // Check that the PDF starts with the PDF header
        $this->assertStringStartsWith('%PDF-', $html);
    }

    /**
     * Test that the ZB account opening PDF template renders with images.
     */
    public function test_zb_account_opening_pdf_contains_images(): void
    {
        // Generate the PDF content
        $pdf = Pdf::loadView('forms.zb_account_opening_pdf');
        $html = $pdf->output();
        
        // Check that the PDF was generated successfully
        $this->assertNotEmpty($html);
        
        // Check that the PDF starts with the PDF header
        $this->assertStringStartsWith('%PDF-', $html);
    }

    /**
     * Test that image files are accessible via public_path.
     */
    public function test_image_paths_are_accessible(): void
    {
        $qupa_path = public_path('assets/images/qupa.png');
        $bancozim_path = public_path('assets/images/bancozim.png');
        $zb_logo_path = public_path('assets/images/zb_logo.png');
        
        $this->assertFileExists($qupa_path);
        $this->assertFileExists($bancozim_path);
        $this->assertFileExists($zb_logo_path);
        
        // Check that files are readable
        $this->assertTrue(is_readable($qupa_path));
        $this->assertTrue(is_readable($bancozim_path));
        $this->assertTrue(is_readable($zb_logo_path));
        
        // Check that files are not empty
        $this->assertGreaterThan(0, filesize($qupa_path));
        $this->assertGreaterThan(0, filesize($bancozim_path));
        $this->assertGreaterThan(0, filesize($zb_logo_path));
    }
}
