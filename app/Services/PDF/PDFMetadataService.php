<?php

namespace App\Services\PDF;

use App\Models\ApplicationState;
use App\Services\PDFLoggingService;

class PDFMetadataService
{
    private PDFLoggingService $logger;

    public function __construct(PDFLoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Generate XMP metadata for the PDF
     */
    public function generateXMPMetadata(ApplicationState $applicationState, array $pdfData): string
    {
        $referenceCode = $applicationState->reference_code ?? 'DRAFT';
        $formId = $pdfData['formId'] ?? 'unknown';
        $applicantName = $this->getApplicantName($pdfData);
        $creationDate = now()->toISOString();

        $xmp = <<<XMP
<?xpacket begin="﻿" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
    <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
        <rdf:Description rdf:about=""
            xmlns:dc="http://purl.org/dc/elements/1.1/"
            xmlns:xmp="http://ns.adobe.com/xap/1.0/"
            xmlns:pdf="http://ns.adobe.com/pdf/1.3/"
            xmlns:bancozim="http://bancozim.com/ns/1.0/">
            
            <!-- Dublin Core Metadata -->
            <dc:title>
                <rdf:Alt>
                    <rdf:li xml:lang="en">Bancozim Application - {$referenceCode}</rdf:li>
                </rdf:Alt>
            </dc:title>
            <dc:creator>
                <rdf:Seq>
                    <rdf:li>Bancozim Loan System</rdf:li>
                </rdf:Seq>
            </dc:creator>
            <dc:subject>
                <rdf:Bag>
                    <rdf:li>Loan Application</rdf:li>
                    <rdf:li>Banking</rdf:li>
                    <rdf:li>Financial Services</rdf:li>
                </rdf:Bag>
            </dc:subject>
            <dc:description>
                <rdf:Alt>
                    <rdf:li xml:lang="en">Loan application form for {$applicantName} - Reference: {$referenceCode}</rdf:li>
                </rdf:Alt>
            </dc:description>
            
            <!-- XMP Basic Metadata -->
            <xmp:CreateDate>{$creationDate}</xmp:CreateDate>
            <xmp:ModifyDate>{$creationDate}</xmp:ModifyDate>
            <xmp:CreatorTool>Bancozim PDF Generator v1.0</xmp:CreatorTool>
            
            <!-- PDF Metadata -->
            <pdf:Producer>Bancozim Loan System</pdf:Producer>
            <pdf:Keywords>loan, application, banking, {$referenceCode}</pdf:Keywords>
            
            <!-- Custom Bancozim Metadata -->
            <bancozim:ReferenceCode>{$referenceCode}</bancozim:ReferenceCode>
            <bancozim:FormId>{$formId}</bancozim:FormId>
            <bancozim:SessionId>{$applicationState->session_id}</bancozim:SessionId>
            <bancozim:Channel>{$applicationState->channel}</bancozim:Channel>
            <bancozim:ApplicantName>{$applicantName}</bancozim:ApplicantName>
            <bancozim:ApplicationDate>{$applicationState->created_at->toISOString()}</bancozim:ApplicationDate>
            <bancozim:Version>1.0</bancozim:Version>
        </rdf:Description>
    </rdf:RDF>
</x:xmpmeta>
<?xpacket end="w"?>
XMP;

        return $xmp;
    }

    /**
     * Apply metadata to PDF
     */
    public function applyMetadata($pdf, ApplicationState $applicationState, array $pdfData): void
    {
        try {
            $domPdf = $pdf->getDomPDF();
            $canvas = $domPdf->get_canvas();
            
            // Set basic PDF info
            $this->setBasicPdfInfo($canvas, $applicationState, $pdfData);
            
            // Add XMP metadata
            $xmpMetadata = $this->generateXMPMetadata($applicationState, $pdfData);
            $this->addXMPMetadata($canvas, $xmpMetadata);
            
            $this->logger->logInfo('PDF metadata applied', [
                'session_id' => $applicationState->session_id,
                'reference_code' => $applicationState->reference_code,
            ]);

        } catch (\Exception $e) {
            $this->logger->logWarning('Failed to apply PDF metadata', [
                'session_id' => $applicationState->session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Set basic PDF document information
     */
    private function setBasicPdfInfo($canvas, ApplicationState $applicationState, array $pdfData): void
    {
        $referenceCode = $applicationState->reference_code ?? 'DRAFT';
        $applicantName = $this->getApplicantName($pdfData);
        
        $info = [
            'Title' => "Bancozim Application - {$referenceCode}",
            'Author' => 'Bancozim Loan System',
            'Subject' => "Loan application for {$applicantName}",
            'Keywords' => "loan, application, banking, {$referenceCode}",
            'Creator' => 'Bancozim PDF Generator v1.0',
            'Producer' => 'Bancozim Loan System',
            'CreationDate' => 'D:' . now()->format('YmdHis') . '+00\'00\'',
            'ModDate' => 'D:' . now()->format('YmdHis') . '+00\'00\'',
        ];

        foreach ($info as $key => $value) {
            $canvas->get_cpdf()->addInfo($key, $value);
        }
    }

    /**
     * Add XMP metadata to PDF
     */
    private function addXMPMetadata($canvas, string $xmpMetadata): void
    {
        // Note: This is a simplified implementation
        // In a production environment, you might want to use a more robust XMP library
        $canvas->get_cpdf()->addInfo('XMP', $xmpMetadata);
    }

    /**
     * Get applicant name from PDF data
     */
    private function getApplicantName(array $pdfData): string
    {
        $firstName = $pdfData['firstName'] ?? $pdfData['first_name'] ?? '';
        $lastName = $pdfData['lastName'] ?? $pdfData['surname'] ?? $pdfData['last_name'] ?? '';
        
        $fullName = trim($firstName . ' ' . $lastName);
        
        return $fullName ?: 'Unknown Applicant';
    }

    /**
     * Generate PDF generation metadata
     */
    public function generateGenerationMetadata(ApplicationState $applicationState, array $options = []): array
    {
        return [
            'generation_id' => \Illuminate\Support\Str::uuid(),
            'session_id' => $applicationState->session_id,
            'reference_code' => $applicationState->reference_code,
            'channel' => $applicationState->channel,
            'form_id' => $applicationState->form_data['formId'] ?? null,
            'generated_at' => now()->toISOString(),
            'generator_version' => '1.0',
            'options' => $options,
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'dompdf_version' => $this->getDomPDFVersion(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'system_info' => [
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
        ];
    }

    /**
     * Get DomPDF version
     */
    private function getDomPDFVersion(): string
    {
        try {
            if (class_exists('\Dompdf\Dompdf')) {
                // Dompdf doesn't have a VERSION constant, try to get it from composer
                $composerFile = base_path('composer.lock');
                if (file_exists($composerFile)) {
                    $composerData = json_decode(file_get_contents($composerFile), true);
                    foreach ($composerData['packages'] ?? [] as $package) {
                        if ($package['name'] === 'dompdf/dompdf') {
                            return $package['version'] ?? 'unknown';
                        }
                    }
                }
                return 'installed';
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        return 'unknown';
    }

    /**
     * Extract metadata from existing PDF
     */
    public function extractMetadata(string $pdfPath): array
    {
        $metadata = [
            'file_info' => [],
            'pdf_info' => [],
            'bancozim_metadata' => [],
        ];

        try {
            // Get file information
            if (file_exists($pdfPath)) {
                $metadata['file_info'] = [
                    'size' => filesize($pdfPath),
                    'created' => filectime($pdfPath),
                    'modified' => filemtime($pdfPath),
                    'permissions' => substr(sprintf('%o', fileperms($pdfPath)), -4),
                ];
            }

            // Extract PDF metadata using a PDF parser
            // Note: This would require a PDF parsing library like TCPDF or similar
            // For now, we'll return basic file info
            
        } catch (\Exception $e) {
            $this->logger->logWarning('Failed to extract PDF metadata', [
                'path' => $pdfPath,
                'error' => $e->getMessage(),
            ]);
        }

        return $metadata;
    }

    /**
     * Validate metadata completeness
     */
    public function validateMetadata(array $metadata): array
    {
        $errors = [];
        
        $requiredFields = [
            'session_id',
            'reference_code',
            'channel',
            'generated_at',
        ];

        foreach ($requiredFields as $field) {
            if (empty($metadata[$field])) {
                $errors[] = "Missing required metadata field: {$field}";
            }
        }

        return $errors;
    }
}
