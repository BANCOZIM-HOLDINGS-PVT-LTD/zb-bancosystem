<?php

namespace App\Services;

use App\Models\ApplicationState;
use App\Models\Product;
use App\Models\MicrobizPackage;
use App\Contracts\PDFGeneratorInterface;
use App\Exceptions\PDF\PDFException;
use App\Exceptions\PDF\PDFGenerationException;
use App\Exceptions\PDF\PDFStorageException;
use App\Exceptions\PDF\PDFIncompleteDataException;
use App\Services\PDF\PDFTemplateService;
use App\Services\PDF\PDFSecurityService;
use App\Services\PDF\PDFValidationService;
use App\Services\PDF\PDFStorageService;
use App\Services\PDF\PDFMetadataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class PDFGeneratorService implements PDFGeneratorInterface
{
    /**
     * The PDF logging service instance
     *
     * @var PDFLoggingService
     */
    protected PDFLoggingService $logger;
    
    /**
     * The system monitoring service instance
     */
    protected SystemMonitoringService $monitoringService;

    /**
     * The PDF template service instance
     */
    protected PDFTemplateService $templateService;

    /**
     * The PDF security service instance
     */
    protected PDFSecurityService $securityService;

    /**
     * The PDF validation service instance
     */
    protected PDFValidationService $validationService;

    /**
     * The PDF storage service instance
     */
    protected PDFStorageService $storageService;

    /**
     * The PDF metadata service instance
     */
    protected PDFMetadataService $metadataService;

    /**
     * Create a new PDF generator service instance
     */
    public function __construct(
        PDFLoggingService $logger,
        SystemMonitoringService $monitoringService,
        PDFTemplateService $templateService,
        PDFSecurityService $securityService,
        PDFValidationService $validationService,
        PDFStorageService $storageService,
        PDFMetadataService $metadataService
    ) {
        $this->logger = $logger;
        $this->monitoringService = $monitoringService;
        $this->templateService = $templateService;
        $this->securityService = $securityService;
        $this->validationService = $validationService;
        $this->storageService = $storageService;
        $this->metadataService = $metadataService;
    }
    /**
     * Generate PDF from completed application with document embedding and metadata
     * 
     * @param ApplicationState $applicationState The application state containing form data
     * @return string Path to the generated PDF file
     * @throws PDFIncompleteDataException When application data is incomplete
     * @throws PDFGenerationException When PDF generation fails
     * @throws PDFStorageException When PDF storage fails
     */
    public function generateApplicationPDF(ApplicationState $applicationState): string
    {
        $startTime = microtime(true);
        
        try {
            $this->logger->logInfo('Starting PDF generation', [
                'session_id' => $applicationState->session_id,
                'current_step' => $applicationState->current_step
            ]);
            
            $formData = $applicationState->form_data ?? [];
            
            // Validate required data
            if (empty($formData)) {
                $this->logger->logError('Application data is missing or incomplete', [
                    'session_id' => $applicationState->session_id,
                    'current_step' => $applicationState->current_step
                ]);
                
                throw new PDFIncompleteDataException(
                    "Application data is missing or incomplete",
                    [
                        'session_id' => $applicationState->session_id,
                        'current_step' => $applicationState->current_step
                    ]
                );
            }
            
            // Check for required form responses
            if (empty($formData['formResponses'] ?? [])) {
                $this->logger->logError('Form responses are missing', [
                    'session_id' => $applicationState->session_id,
                    'has_form_data' => isset($formData['formResponses'])
                ]);
                
                throw new PDFIncompleteDataException(
                    "Form responses are missing",
                    [
                        'session_id' => $applicationState->session_id,
                        'has_form_data' => isset($formData['formResponses'])
                    ]
                );
            }
            
            $employer = $formData['employer'] ?? '';
            $hasAccount = $formData['hasAccount'] ?? false;
            $responses = $formData['formResponses'] ?? [];
            
            // Determine which PDF template to use
            $hasAccount = $formData['hasAccount'] ?? false;
            
            // Explicitly force hasAccount to false if wantsAccount is true
            // This ensures that even if hasAccount is set to true (e.g. by default or error),
            // if the user explicitly wants to open an account, we treat it as an account opening application.
            if (($formData['wantsAccount'] ?? false) === true) {
                $hasAccount = false;
            }
            
            $template = $this->determineTemplate($employer, $hasAccount);
            
            $this->logger->logDebug('Using PDF template', [
                'session_id' => $applicationState->session_id,
                'template' => $template,
                'employer' => $employer,
                'has_account' => $hasAccount
            ]);

            // Add FCB Report Data for Account Holder Loans
            $fcbData = null;
            if ($template === 'forms.account_holders_pdf') {
                $nationalId = $formData['formResponses']['nationalIdNumber'] 
                    ?? $formData['formResponses']['nationalId'] 
                    ?? $formData['nationalId'] 
                    ?? '00000000A00'; // Fallback
                    
                // Use the FCB Service (We can instantiate directly or inject. For now direct instantiation for simplicity in this specific scope)
                $fcbService = new \App\Services\FCBService();
                $fcbData = $fcbService->checkCreditStatus($nationalId);
                
                $this->logger->logDebug('Fetched FCB Data', [
                    'national_id' => $nationalId,
                    'report_serial' => $fcbData['report_serial'] ?? 'N/A'
                ]);
            }
            
            // Prepare data for PDF
            $pdfData = $this->preparePDFData($applicationState);
            
            // Add FCB data to pdfData
            if ($fcbData) {
                $pdfData['fcbData'] = $fcbData;
            }
            
            // Process and prepare documents for embedding
            $pdfData = $this->prepareDocumentsForEmbedding($pdfData);
            
            $this->logger->logDebug('PDF data prepared', [
                'session_id' => $applicationState->session_id,
                'has_documents' => isset($pdfData['documentSummary']),
                'document_count' => $pdfData['documentSummary']['totalDocuments'] ?? 0
            ]);
            
            try {
                // Generate PDF
                $pdf = PDF::loadView($template, $pdfData);
                
                // Set paper size and orientation
                $pdf->setPaper('A4', 'portrait');
                
                // Set PDF options for better quality and performance
                $pdf->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                ]);
                
                // Add PDF metadata and properties
                $this->addPdfMetadata($pdf, $pdfData, $applicationState);
                
                // Apply PDF security if needed
                $this->applyPdfSecurity($pdf, $applicationState);
                
                $this->logger->logDebug('PDF document created successfully', [
                    'session_id' => $applicationState->session_id
                ]);
            } catch (\Exception $e) {
                // Log the detailed error
                $this->logger->logError('PDF generation failed', [
                    'session_id' => $applicationState->session_id,
                    'template' => $template,
                    'error' => $e->getMessage()
                ], $e);
                
                throw new PDFGenerationException(
                    "Failed to generate PDF: {$e->getMessage()}",
                    [
                        'session_id' => $applicationState->session_id,
                        'template' => $template,
                        'error' => $e->getMessage()
                    ],
                    0,
                    $e
                );
            }
            
            // Generate filename
            $filename = $this->generateFilename($applicationState);
            
            // Save PDF to storage
            try {
                $path = 'applications/' . $filename;
                
                // Ensure the applications directory exists
                if (!Storage::disk('public')->exists('applications')) {
                    Storage::disk('public')->makeDirectory('applications');
                    $this->logger->logDebug('Created applications directory', [
                        'session_id' => $applicationState->session_id
                    ]);
                }
                
                Storage::disk('public')->put($path, $pdf->output());
                
                // Verify the file was saved successfully
                if (!Storage::disk('public')->exists($path)) {
                    throw new \Exception("Failed to verify PDF file existence after saving");
                }
                
                // Update application state with PDF path and metadata
                $this->updateApplicationWithPdfPath($applicationState, $path);
                
                $fileSize = Storage::disk('public')->size($path);
                
                // Log successful PDF generation
                $this->logger->logInfo('PDF generated and stored successfully', [
                    'session_id' => $applicationState->session_id,
                    'path' => $path,
                    'size' => $fileSize,
                    'filename' => $filename
                ]);
                
                // Log performance metrics
                $endTime = microtime(true);
                $duration = $endTime - $startTime;
                $this->logger->logPerformance('PDF generation completed', $duration, [
                    'session_id' => $applicationState->session_id,
                    'file_size' => $fileSize,
                    'template' => $template
                ]);
                
                // Record monitoring metrics
                $this->monitoringService->recordPDFGenerationMetrics(
                    $applicationState->session_id,
                    $duration,
                    true
                );
                
                return $path;
            } catch (\Exception $e) {
                $this->logger->logError('PDF storage failed', [
                    'session_id' => $applicationState->session_id,
                    'filename' => $filename,
                    'path' => $path ?? null,
                    'critical' => true
                ], $e);
                
                throw new PDFStorageException(
                    "Failed to store PDF: {$e->getMessage()}",
                    [
                        'session_id' => $applicationState->session_id,
                        'filename' => $filename,
                        'error' => $e->getMessage()
                    ],
                    0,
                    $e
                );
            }
        } catch (PDFException $e) {
            // Record monitoring metrics for PDF exceptions
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            $this->monitoringService->recordPDFGenerationMetrics(
                $applicationState->session_id,
                $duration,
                false,
                $e->getMessage()
            );
            
            // Re-throw PDFExceptions but log them first
            $this->logger->logError('PDF Exception occurred', [
                'session_id' => $applicationState->session_id,
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext()
            ], $e);
            
            throw $e;
        } catch (\Exception $e) {
            // Record monitoring metrics for unexpected errors
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            $this->monitoringService->recordPDFGenerationMetrics(
                $applicationState->session_id,
                $duration,
                false,
                $e->getMessage()
            );
            
            // Wrap any other exceptions in PDFGenerationException
            $this->logger->logError('Unexpected error during PDF generation', [
                'session_id' => $applicationState->session_id,
                'critical' => true
            ], $e);
            
            throw new PDFGenerationException(
                "Unexpected error during PDF generation: {$e->getMessage()}",
                [
                    'session_id' => $applicationState->session_id,
                    'error' => $e->getMessage()
                ],
                0,
                $e
            );
        }
    }
    
    /**
     * Add metadata and properties to the PDF document
     * 
     * @param \Barryvdh\DomPDF\PDF $pdf The PDF instance
     * @param array $pdfData The PDF data array
     * @param ApplicationState $applicationState The application state
     * @return void
     */
    private function addPdfMetadata($pdf, array $pdfData, ApplicationState $applicationState): void
    {
        // Get applicant name for metadata
        $firstName = $pdfData['firstName'] ?? $pdfData['formResponses']['firstName'] ?? '';
        $lastName = $pdfData['surname'] ?? $pdfData['lastName'] ?? $pdfData['formResponses']['surname'] ?? '';
        $applicantName = trim("$firstName $lastName");
        
        // Get application type
        $formId = $pdfData['formId'] ?? '';
        $applicationType = $this->getApplicationTypeFromFormId($formId);
        
        // Get reference code
        $referenceCode = $pdfData['referenceCode'] ?? $applicationState->resume_code ?? '';
        
        // Create title
        $title = $applicationType . ' Application - ' . ($applicantName ?: 'Applicant');
        if ($referenceCode) {
            $title .= ' (Ref: ' . $referenceCode . ')';
        }
        
        // Create description
        $description = $applicationType . ' application form for ' . ($applicantName ?: 'applicant');
        $description .= ' submitted on ' . Carbon::now()->format('Y-m-d');
        if ($referenceCode) {
            $description .= '. Reference code: ' . $referenceCode;
        }
        
        // Get application number
        $applicationNumber = $pdfData['applicationNumber'] ?? '';
        
        // Set PDF metadata using the DomPDF instance
        $domPdf = $pdf->getDomPDF();
        $canvas = $domPdf->get_canvas();
        
        // Set PDF document information
        $canvas->add_info('Title', $title);
        $canvas->add_info('Author', config('app.name', 'ZB Bank'));
        $canvas->add_info('Subject', $applicationType . ' Application Form');
        $canvas->add_info('Keywords', 'application, ' . strtolower($applicationType) . ', form, ' . strtolower(config('app.name', 'zb bank')));
        $canvas->add_info('Creator', config('app.name', 'ZB Bank') . ' Application System');
        $canvas->add_info('Producer', 'ZB PDF Generator');
        $canvas->add_info('CreationDate', date('Y-m-d H:i:s'));
        $canvas->add_info('ModDate', date('Y-m-d H:i:s'));
        $canvas->add_info('Trapped', 'False');
        
        // Add custom metadata
        $canvas->add_info('ApplicationNumber', $applicationNumber);
        $canvas->add_info('ReferenceCode', $referenceCode);
        $canvas->add_info('ApplicantName', $applicantName);
        $canvas->add_info('ApplicationType', $applicationType);
        $canvas->add_info('GeneratedAt', Carbon::now()->format('Y-m-d H:i:s'));
        $canvas->add_info('SessionId', $applicationState->session_id);
        
        // Add XMP metadata if supported
        if (method_exists($canvas, 'add_xmp_metadata')) {
            $xmpMetadata = $this->generateXmpMetadata($pdfData, $applicationState);
            $canvas->add_xmp_metadata($xmpMetadata);
        }
    }
    
    /**
     * Apply security settings to the PDF document
     *
     * @param \Barryvdh\DomPDF\PDF $pdf The PDF instance
     * @param ApplicationState $applicationState The application state
     * @return void
     */
    private function applyPdfSecurity($pdf, ApplicationState $applicationState): void
    {
        // Get the DomPDF instance
        $domPdf = $pdf->getDomPDF();

        // Check if we should apply security based on application type or configuration
        $shouldApplySecurity = env('PDF_ENCRYPTION_ENABLED', true);

        if ($shouldApplySecurity) {
            // Generate a password based on application data for document opening
            // In a real-world scenario, this would be securely stored and managed
            $openPassword = null; // No password required to open the document

            // Generate a cryptographically secure permissions password (owner password)
            $passwordLength = (int) env('PDF_OWNER_PASSWORD_LENGTH', 16);
            $permissionPassword = $this->generateSecurePassword($passwordLength, $applicationState->session_id);
            
            // Set encryption and permissions
            // Permissions: 
            // - Allow printing
            // - Disallow modification
            // - Allow copy of content
            // - Disallow annotation
            $domPdf->get_canvas()->get_cpdf()->setEncryption(
                $openPassword,
                $permissionPassword,
                ['print', 'copy'],
                1 // 128-bit encryption
            );
        }
    }

    /**
     * Generate a cryptographically secure password for PDF protection
     *
     * @param int $length The desired password length
     * @param string $sessionId The session ID for additional entropy
     * @return string The generated secure password
     */
    private function generateSecurePassword(int $length, string $sessionId): string
    {
        // Use cryptographically secure random bytes
        $randomBytes = random_bytes($length);

        // Convert to base64 and clean up for password use
        $password = base64_encode($randomBytes);

        // Remove characters that might cause issues in PDF passwords
        $password = str_replace(['+', '/', '='], ['A', 'B', 'C'], $password);

        // Ensure we have the exact length requested
        $password = substr($password, 0, $length);

        // Add session-specific entropy (but don't make it predictable)
        $sessionHash = hash('sha256', $sessionId . config('app.key') . time());
        $sessionEntropy = substr($sessionHash, 0, 4);

        // Mix the random password with session entropy
        $finalPassword = substr($password, 0, $length - 4) . $sessionEntropy;

        // Log password generation (without the actual password)
        $this->logger->logInfo('PDF password generated', [
            'session_id' => $sessionId,
            'password_length' => strlen($finalPassword),
            'entropy_added' => true
        ]);

        return $finalPassword;
    }

    /**
     * Generate XMP metadata for the PDF
     * 
     * @param array $pdfData The PDF data array
     * @param ApplicationState $applicationState The application state
     * @return string XMP metadata XML
     */
    private function generateXmpMetadata(array $pdfData, ApplicationState $applicationState): string
    {
        // Get basic information
        $firstName = $pdfData['firstName'] ?? $pdfData['formResponses']['firstName'] ?? '';
        $lastName = $pdfData['surname'] ?? $pdfData['lastName'] ?? $pdfData['formResponses']['surname'] ?? '';
        $applicantName = trim("$firstName $lastName");
        $formId = $pdfData['formId'] ?? '';
        $applicationType = $this->getApplicationTypeFromFormId($formId);
        $referenceCode = $pdfData['referenceCode'] ?? $applicationState->resume_code ?? '';
        $applicationNumber = $pdfData['applicationNumber'] ?? '';
        
        // Create XMP metadata XML
        $xmp = '<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/">
      <dc:format>application/pdf</dc:format>
      <dc:title>
        <rdf:Alt>
          <rdf:li xml:lang="x-default">' . htmlspecialchars($applicationType . ' Application - ' . ($applicantName ?: 'Applicant')) . '</rdf:li>
        </rdf:Alt>
      </dc:title>
      <dc:creator>
        <rdf:Seq>
          <rdf:li>' . htmlspecialchars(config('app.name', 'ZB Bank')) . '</rdf:li>
        </rdf:Seq>
      </dc:creator>
      <dc:description>
        <rdf:Alt>
          <rdf:li xml:lang="x-default">' . htmlspecialchars($applicationType . ' application form for ' . ($applicantName ?: 'applicant')) . '</rdf:li>
        </rdf:Alt>
      </dc:description>
    </rdf:Description>
    <rdf:Description rdf:about="" xmlns:xmp="http://ns.adobe.com/xap/1.0/">
      <xmp:CreatorTool>' . htmlspecialchars(config('app.name', 'ZB Bank') . ' Application System') . '</xmp:CreatorTool>
      <xmp:CreateDate>' . date('Y-m-d\TH:i:s') . '</xmp:CreateDate>
      <xmp:ModifyDate>' . date('Y-m-d\TH:i:s') . '</xmp:ModifyDate>
      <xmp:MetadataDate>' . date('Y-m-d\TH:i:s') . '</xmp:MetadataDate>
    </rdf:Description>
    <rdf:Description rdf:about="" xmlns:pdf="http://ns.adobe.com/pdf/1.3/">
      <pdf:Producer>' . htmlspecialchars('ZB PDF Generator') . '</pdf:Producer>
      <pdf:Keywords>' . htmlspecialchars('application, ' . strtolower($applicationType) . ', form, ' . strtolower(config('app.name', 'zb bank'))) . '</pdf:Keywords>
    </rdf:Description>
    <rdf:Description rdf:about="" xmlns:pdfx="http://ns.adobe.com/pdfx/1.3/">
      <pdfx:ApplicationNumber>' . htmlspecialchars($applicationNumber) . '</pdfx:ApplicationNumber>
      <pdfx:ReferenceCode>' . htmlspecialchars($referenceCode) . '</pdfx:ReferenceCode>
      <pdfx:ApplicantName>' . htmlspecialchars($applicantName) . '</pdfx:ApplicantName>
      <pdfx:ApplicationType>' . htmlspecialchars($applicationType) . '</pdfx:ApplicationType>
      <pdfx:GeneratedAt>' . Carbon::now()->format('Y-m-d\TH:i:s') . '</pdfx:GeneratedAt>
      <pdfx:SessionId>' . htmlspecialchars($applicationState->session_id) . '</pdfx:SessionId>
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>
<?xpacket end="w"?>';
        
        return $xmp;
    }
    
    /**
     * Get application type from form ID
     * 
     * @param string $formId The form ID
     * @return string Application type
     */
    private function getApplicationTypeFromFormId(string $formId): string
    {
        $types = [
            'account_holder_loan_application.json' => 'Account Holder Loan',
            'ssb_account_opening_form.json' => 'SSB Loan',
            'pensioner_loan_application.json' => 'Government Pensioner Loan',
            'individual_account_opening.json' => 'ZB Account Opening',
            'smes_business_account_opening.json' => 'SME Business Account',
        ];
        
        return $types[$formId] ?? 'Application';
    }
    
    /**
     * Process and prepare documents for embedding in PDF
     * 
     * @param array $pdfData The PDF data array
     * @return array Updated PDF data with processed documents
     */
    private function prepareDocumentsForEmbedding(array $pdfData): array
    {
        // IMPORTANT: Always initialize image data structures to prevent "Undefined variable" errors in templates
        // Templates like ssb_form_pdf.blade.php access $signatureImageData['data'] which requires the array structure
        $emptyImageData = [
            'data' => '',
            'type' => '',
            'width' => 0,
            'height' => 0,
            'aspectRatio' => 1,
        ];
        $pdfData['selfieImageData'] = $emptyImageData;
        $pdfData['signatureImageData'] = $emptyImageData;
        $pdfData['idImageData'] = $emptyImageData;
        $pdfData['payslipImageData'] = $emptyImageData;
        $pdfData['proofOfResidenceImageData'] = $emptyImageData;

        // Skip further processing if no documents are available
        if (!isset($pdfData['documents']) || empty($pdfData['documents'])) {
            return $pdfData;
        }

        // Process selfie image if available
        if (!empty($pdfData['selfieImage'])) {
            $pdfData['selfieImageData'] = $this->processBase64Image($pdfData['selfieImage']);
        }

        // Process signature image if available
        if (!empty($pdfData['signatureImage'])) {
            $pdfData['signatureImageData'] = $this->processBase64Image($pdfData['signatureImage']);
            
            // Update the simple signatureImage variable with the processed data URI
            // This ensures views using {{ $signatureImage }} work correctly with the processing result
            if (!empty($pdfData['signatureImageData']['data'])) {
                $pdfData['signatureImage'] = $pdfData['signatureImageData']['data'];
            }
        }
        
        // Process uploaded documents if available
        if (isset($pdfData['documentsByType']) && !empty($pdfData['documentsByType'])) {
            foreach ($pdfData['documentsByType'] as $type => $documents) {
                foreach ($documents as $index => $document) {
                    if (isset($document['path']) && !empty($document['path'])) {
                        $pdfData['documentsByType'][$type][$index]['embeddedData'] = 
                            $this->processDocumentForEmbedding($document['path'], $document['type']);
                    }
                }
            }
        }
        
        // Add document summary for easy access in templates
        $pdfData['documentSummary'] = $this->generateDocumentSummary($pdfData);
        
        // Map documents to specific variables expected by templates
        // This ensures backward compatibility with templates expecting $idImageData, $payslipImageData, etc.
        
        // 1. ID Document
        $idKeys = ['national_id', 'nationalId', 'id', 'id_document', 'identity_document'];
        foreach ($idKeys as $key) {
            if (isset($pdfData['documentsByType'][$key][0]['embeddedData'])) {
                $pdfData['idImageData'] = $pdfData['documentsByType'][$key][0]['embeddedData'];
                break;
            }
        }
        
        // 2. Payslip
        $payslipKeys = ['payslip', 'pay_slip', 'current_payslip', 'salary_slip'];
        foreach ($payslipKeys as $key) {
            if (isset($pdfData['documentsByType'][$key][0]['embeddedData'])) {
                $pdfData['payslipImageData'] = $pdfData['documentsByType'][$key][0]['embeddedData'];
                break;
            }
        }
        
        // 3. Proof of Residence
        $porKeys = ['proof_of_residence', 'proofOfResidence', 'residence_proof', 'address_proof'];
        foreach ($porKeys as $key) {
            if (isset($pdfData['documentsByType'][$key][0]['embeddedData'])) {
                $pdfData['proofOfResidenceImageData'] = $pdfData['documentsByType'][$key][0]['embeddedData'];
                break;
            }
        }
        
        return $pdfData;
    }
    
    /**
     * Process a base64 encoded image for embedding in PDF
     * 
     * @param string $base64Image Base64 encoded image string
     * @return array Processed image data
     */
    private function processBase64Image(string $base64Image): array
    {
        // Extract image data from base64 string
        $imageData = null;
        $imageType = null;
        
        // CASE 1: Handle actual Base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $imageType = $matches[1];
            $base64Data = substr($base64Image, strpos($base64Image, ',') + 1);
            $imageData = base64_decode($base64Data);
            
            // Generate a temporary file path
            $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.' . $imageType;
            file_put_contents($tempFilePath, $imageData);
    
            // Get image dimensions with error handling
            $imageInfo = @getimagesize($tempFilePath);
    
            // Clean up temporary file
            @unlink($tempFilePath);
    
            // Handle getimagesize failure
            if ($imageInfo === false) {
                return [
                    'data' => $base64Image,
                    'type' => $imageType,
                    'width' => 100,
                    'height' => 100, 
                    'aspectRatio' => 1,
                ];
            }
    
            $width = $imageInfo[0];
            $height = $imageInfo[1];
    
            return [
                'data' => $base64Image,
                'type' => $imageType,
                'width' => $width,
                'height' => $height,
                'aspectRatio' => $height > 0 ? $width / $height : 1,
            ];
        }
        
        // CASE 2: Handle URL or File Path (Signature passed as URL)
        // Convert URL to path if needed (reuse logic similar to processDocumentForEmbedding)
        $path = $base64Image;
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($path);
            if (isset($parsed['path'])) {
                $cleanPath = ltrim($parsed['path'], '/');
                if (str_starts_with($cleanPath, 'storage/')) {
                    $path = substr($cleanPath, 8);
                } else {
                    $path = $cleanPath;
                }
            }
        }
        
        // Check if it's a valid local file path
        if (Storage::disk('public')->exists($path)) {
            $content = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path);
            $base64 = base64_encode($content);
            $dataUri = 'data:' . $mimeType . ';base64,' . $base64;
            
            // Get dimensions
            $width = 0;
            $height = 0;
            try {
                 // Create temp file for dimensions
                $tempFilePath = sys_get_temp_dir() . '/' . uniqid();
                file_put_contents($tempFilePath, $content);
                $imageInfo = @getimagesize($tempFilePath);
                @unlink($tempFilePath);
                
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            } catch (\Exception $e) {
                // Ignore dimension errors
            }
            
            return [
                'data' => $dataUri,
                'type' => str_replace('image/', '', $mimeType),
                'width' => $width,
                'height' => $height,
                'aspectRatio' => $height > 0 ? $width / $height : 1,
            ];
        }
        
        // Return empty data if processing fails
        return [
            'data' => '',
            'type' => '',
            'width' => 0,
            'height' => 0,
            'aspectRatio' => 1,
        ];
    }
    
    /**
     * Process a document for embedding in PDF
     * 
     * @param string $path Path to the document
     * @param string $type Document MIME type
     * @return array Processed document data
     */
    private function processDocumentForEmbedding(string $path, string $type): array
    {
        // Default empty result
        $result = [
            'data' => '',
            'type' => $type,
            'isImage' => false,
            'isPdf' => false,
            'width' => 0,
            'height' => 0,
            'aspectRatio' => 1,
            'pages' => 1,
        ];
        
        try {
        // Handle URLs by trying to convert to relative path (Backwards Compatibility)
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($path);
            if (isset($parsed['path'])) {
                // Remove leading slash
                $cleanPath = ltrim($parsed['path'], '/');
                
                // If path starts with 'storage/', remove it to get disk-relative path
                // e.g. /storage/documents/123.jpg -> documents/123.jpg
                if (str_starts_with($cleanPath, 'storage/')) {
                    $path = substr($cleanPath, 8);
                } else {
                    $path = $cleanPath;
                }
            }
        }

        // Check if file exists in storage
        if (!Storage::disk('public')->exists($path)) {
                return $result;
            }
            
            // Get file contents
            $fileContents = Storage::disk('public')->get($path);
            
            // Process based on file type
            if (strpos($type, 'image/') === 0) {
                // Handle image files
                $result['isImage'] = true;
                
                // Create a temporary file to get image dimensions
                $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.' . pathinfo($path, PATHINFO_EXTENSION);
                file_put_contents($tempFilePath, $fileContents);

                // Get image dimensions with error handling
                $imageInfo = @getimagesize($tempFilePath);

                // Clean up temporary file
                @unlink($tempFilePath);

                if ($imageInfo !== false) {
                    $result['width'] = $imageInfo[0];
                    $result['height'] = $imageInfo[1];
                    $result['aspectRatio'] = $imageInfo[1] > 0 ? $imageInfo[0] / $imageInfo[1] : 1;
                }

                // Convert to base64 for embedding
                $base64Data = base64_encode($fileContents);
                $result['data'] = 'data:' . $type . ';base64,' . $base64Data;
            } elseif ($type === 'application/pdf') {
                // Handle PDF files
                $result['isPdf'] = true;
                
                // Count pages in PDF
                $tempFilePath = sys_get_temp_dir() . '/' . uniqid() . '.pdf';
                file_put_contents($tempFilePath, $fileContents);
                
                // Use external library or command to count PDF pages
                // This is a simplified approach - in production, use a proper PDF library
                $pageCount = 1;
                if (class_exists('Imagick')) {
                    try {
                        $imagick = new \Imagick();
                        $imagick->pingImage($tempFilePath);
                        $pageCount = $imagick->getNumberImages();
                    } catch (\Exception $e) {
                        // Fallback to default
                    }
                }
                
                $result['pages'] = $pageCount;
                
                // For PDFs, we'll store the path rather than embedding the full content
                $result['path'] = $path;
                
                // Clean up temporary file
                unlink($tempFilePath);
            } else {
                // For other document types, just store the path
                $result['path'] = $path;
            }
            
            return $result;
        } catch (\Exception $e) {
            // Log error
            \Log::error('Error processing document for embedding: ' . $e->getMessage());
            return $result;
        }
    }
    
    /**
     * Generate a summary of all documents for easy access in templates
     * 
     * @param array $pdfData The PDF data array
     * @return array Document summary
     */
    private function generateDocumentSummary(array $pdfData): array
    {
        $summary = [
            'hasSelfie' => !empty($pdfData['selfieImage']),
            'hasSignature' => !empty($pdfData['signatureImage']),
            'documentTypes' => [],
            'totalDocuments' => 0,
        ];
        
        // Count documents by type
        if (isset($pdfData['documentsByType'])) {
            foreach ($pdfData['documentsByType'] as $type => $documents) {
                $count = count($documents);
                $summary['documentTypes'][$type] = [
                    'count' => $count,
                    'label' => $this->getDocumentTypeLabel($type),
                    'documents' => $documents,
                ];
                $summary['totalDocuments'] += $count;
            }
        }
        
        return $summary;
    }
    
    /**
     * Get a human-readable label for document types
     * 
     * @param string $type Document type code
     * @return string Human-readable label
     */
    private function getDocumentTypeLabel(string $type): string
    {
        $labels = [
            'id' => 'National ID',
            'proofOfResidence' => 'Proof of Residence',
            'payslip' => 'Payslip',
            'bankStatement' => 'Bank Statement',
            'passport' => 'Passport',
            'driversLicense' => 'Driver\'s License',
            'businessRegistration' => 'Business Registration',
            'taxClearance' => 'Tax Clearance',
            'financialStatements' => 'Financial Statements',
        ];
        
        return $labels[$type] ?? ucfirst(preg_replace('/([A-Z])/', ' $1', $type));
    }
    
    /**
     * Update application state with PDF path
     * 
     * @param ApplicationState $applicationState The application state to update
     * @param string $pdfPath Path to the generated PDF
     * @return void
     */
    private function updateApplicationWithPdfPath(ApplicationState $applicationState, string $pdfPath): void
    {
        $formData = $applicationState->form_data ?? [];
        $formData['pdfPath'] = $pdfPath;
        $formData['pdfGeneratedAt'] = Carbon::now()->format('Y-m-d H:i:s');
        
        $applicationState->form_data = $formData;
        $applicationState->save();
    }
    

    /**
     * Determine which PDF template to use
     */
    private function determineTemplate(string $employer, bool $hasAccount): string
    {
        // Check for SSB/Government employers (handle multiple possible values)
        if (in_array($employer, ['goz-ssb', 'government-ssb', 'ssb', 'government'])) {
            return 'forms.ssb_form_pdf';
        }
        
        if ($employer === 'entrepreneur') {
            return 'forms.sme_account_opening_pdf';
        }
        
        if ($employer === 'government-pensioner') {
            return 'forms.pensioner_loan_pdf';
        }
        
        if (!$hasAccount) {
            return 'forms.zb_account_opening_pdf';
        }
        
        return 'forms.account_holders_pdf';
    }
    
    /**
     * Get default fields for specific template type
     */
    private function getDefaultFieldsForTemplate(string $template): array
    {
        // Base fields common to all forms
        $baseFields = [
            // Personal Information
            'title' => '',
            'surname' => '',
            'firstName' => '',
            'middleName' => '',
            'dateOfBirth' => '',
            'gender' => '',
            'maritalStatus' => '',
            'nationality' => 'Zimbabwean',
            'nationalID' => '',
            'nationalIdNumber' => '',
            'passportNumber' => '',
            
            // Contact Information
            'mobile' => '',
            'cellNumber' => '',
            'whatsApp' => '',
            'email' => '',
            'emailAddress' => '',
            
            // Address Information
            'residentialAddress' => '',
            'permanentAddress' => '',
            'city' => '',
            'province' => '',
            'propertyOwnership' => '',
            'periodAtAddress' => '',
            
            // Admin/Delivery Information
            'deliveryStatus' => 'Future',
            'agent' => '',
            'team' => '',
            
            // Declaration
            'declaration' => [
                'fullName' => '',
                'date' => '',
                'signature' => ''
            ]
        ];
        
        switch ($template) {
            case 'forms.ssb_form_pdf':
                return array_merge($baseFields, [
                    // Employment Information (SSB specific)
                    'responsibleMinistry' => '',
                    'department' => '',
                    'employerName' => '',
                    'employerAddress' => '',
                    'employmentStatus' => '',
                    'jobTitle' => '',
                    'dateOfEmployment' => '',
                    'employeeNumber' => '',
                    'employmentNumber' => '',
                    'paypoint' => '',
                    'headOfInstitution' => '',
                    'headOfInstitutionCell' => '',
                    'currentNetSalary' => '',
                    
                    // Spouse/Next of Kin Details
                    'spouseDetails' => [
                        ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => ''],
                        ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => ''],
                        ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => '']
                    ],
                    
                    // Banking Details
                    'bankName' => '',
                    'branch' => '',
                    'accountNumber' => '',
                    
                    // Other Loans
                    'otherLoans' => [
                        ['institution' => '', 'monthlyInstallment' => '', 'currentBalance' => '', 'maturityDate' => ''],
                        ['institution' => '', 'monthlyInstallment' => '', 'currentBalance' => '', 'maturityDate' => '']
                    ],
                    
                    // Loan Details
                    'loanAmount' => '',
                    'loanTenure' => '',
                    'loanTerm' => '',
                    'monthlyPayment' => '',
                    'interestRate' => '',
                    'creditFacilityType' => '',
                    'purposeOfLoan' => '',
                    'purposeAsset' => '',
                    'checkLetter' => '',
                ]);

            case 'forms.pensioner_loan_pdf':
                return array_merge($baseFields, [
                    // Pensioner specific fields (mostly similar to SSB)
                    'responsibleMinistry' => '',
                    'employerName' => 'Government Pensioner',
                    'employerAddress' => '',
                    'employmentStatus' => 'Pensioner',
                    'jobTitle' => 'Pensioner',
                    'dateOfEmployment' => '',
                    'pensionNumber' => '',
                    'employmentNumber' => '', // Using this for Pension Number mapping
                    'headOfInstitution' => '',
                    'headOfInstitutionCell' => '',
                    'netSalary' => '', // Pension amount
                    
                    // Spouse/Next of Kin
                    'spouseDetails' => [
                        ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => ''],
                        ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => '']
                    ],
                    
                    // Banking
                    'bankName' => '',
                    'branch' => '',
                    'accountNumber' => '',
                    
                    // Other Loans
                    'otherLoans' => [
                        ['institution' => '', 'monthlyInstallment' => '', 'currentBalance' => '', 'maturityDate' => ''],
                        ['institution' => '', 'monthlyInstallment' => '', 'currentBalance' => '', 'maturityDate' => '']
                    ],
                    
                    // Loan Details
                    'loanAmount' => '',
                    'loanTenure' => '',
                    'monthlyPayment' => '',
                    'creditFacilityType' => '',
                    'purposeOfLoan' => '',
                ]);
                
            case 'forms.zb_account_opening_pdf':
                return array_merge($baseFields, [
                    // ZB Account specific fields
                    'accountNumber' => '',
                    'accountType' => '',
                    'accountCurrency' => 'USD',
                    'initialDeposit' => '',
                    'serviceCenter' => '',
                    
                    // Additional personal details
                    'maidenName' => '',
                    'otherNames' => '',
                    'placeOfBirth' => '',
                    'citizenship' => '',
                    'dependents' => '',
                    'driversLicense' => '',
                    'passportExpiry' => '',
                    'countryOfResidence' => 'Zimbabwe',
                    'highestEducation' => '',
                    'hobbies' => '',
                    
                    // Contact details
                    'telephoneRes' => '',
                    'bus' => '',
                    
                    // Employment
                    'employerName' => '',
                    'occupation' => '',
                    'businessDescription' => '',
                    'employerType' => '',
                    'employerAddress' => '',
                    'employerContact' => '',
                    'grossMonthlySalary' => '',
                    'otherIncome' => '',
                    
                    // Spouse details
                    'spouseTitle' => '',
                    'spouseFirstName' => '',
                    'spouseSurname' => '',
                    'spouseAddress' => '',
                    'spouseIdNumber' => '',
                    'spouseContact' => '',
                    'spouseRelationship' => '',
                    'spouseEmail' => '',
                    'spouseDetails' => [
                        ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => '', 'emailAddress' => '']
                    ],
                    
                    // Services
                    'smsNumber' => '',
                    'eStatementsEmail' => '',
                    'mobileMoneyNumber' => '',
                    'eWalletNumber' => '',
                    
                    // Funeral cover
                    'funeralCover' => [
                        'dependents' => []
                    ]
                ]);
                
            case 'forms.account_holders_pdf':
                return array_merge($baseFields, [
                    // Account holders specific
                    'responsiblePaymaster' => '',
                    'employerName' => '',
                    'employerAddress' => '',
                    'employmentStatus' => '',
                    'jobTitle' => '',
                    'dateOfEmployment' => '',
                    'headOfInstitution' => '',
                    'headOfInstitutionCell' => '',
                    'employmentNumber' => '',
                    'currentNetSalary' => '',
                    
                    // Loan details
                    'loanTenure' => '12',
                    'monthlyPayment' => '',
                    
                    // Next of kin (variable array)
                    'nextOfKin' => [
                        ['fullName' => '', 'relationship' => '', 'phoneNumber' => '', 'residentialAddress' => '']
                    ],
                    
                    // Banking
                    'bankName' => '',
                    'branch' => '',
                    'accountNumber' => '',
                    
                    // Other loans (variable array)
                    'otherLoans' => [
                        ['institution' => '', 'repayment' => '']
                    ]
                ]);
                
            case 'forms.sme_business_pdf':
                return array_merge($baseFields, [
                    // Business Information
                    'businessName' => '',
                    'businessRegistration' => '',
                    'businessType' => '',
                    'dateEstablished' => '',
                    'businessAddress' => '',
                    'businessCity' => '',
                    'businessProvince' => '',
                    'postalAddress' => '',
                    'businessTelephone' => '',
                    'businessMobile' => '',
                    'businessEmail' => '',
                    'industrySector' => '',
                    'numberOfEmployees' => '',
                    'monthlyTurnover' => '',
                    
                    // Owner information
                    'positionInBusiness' => '',
                    
                    // Financial information
                    'monthlyRevenue' => '',
                    'annualRevenue' => '',
                    'otherMonthlyIncome' => '',
                    'otherAnnualIncome' => '',
                    'totalMonthlyIncome' => '',
                    'totalAnnualIncome' => '',
                    
                    // Account details
                    'accountType' => '',
                    'initialDeposit' => '',
                    'depositMethod' => '',
                    'servicesRequired' => [],
                    
                    // Banking
                    'bankName' => '',
                    'branch' => '',
                    'accountNumber' => ''
                ]);
                
            case 'forms.sme_account_opening_pdf':
                return array_merge($baseFields, [
                    // Business registration
                    'businessType' => '',
                    'loanType' => '',
                    'registeredName' => '',
                    'tradingName' => '',
                    'typeOfBusiness' => '',
                    'businessAddress' => '',
                    'periodAtLocation' => '',
                    'initialCapital' => '',
                    'incorporationDate' => '',
                    'incorporationNumber' => '',
                    'contactPhone' => '',
                    'businessEmail' => '',
                    
                    // Employee type
                    'employeeType' => 'Fulltime and Owner',
                    
                    // Capital sources
                    'capitalSources' => [
                        'ownSavings' => false,
                        'familyGift' => false,
                        'loan' => false,
                        'otherSpecify' => ''
                    ],
                    
                    // Customer base
                    'customerBase' => [
                        'individuals' => false
                    ],
                    
                    // Customer location
                    'customerLocation' => 'This Town'
                ]);
                
            default:
                return $baseFields;
        }
    }
    
    /**
     * Prepare data for PDF generation with enhanced formatting and field support
     * 
     * @param ApplicationState $applicationState The application state containing form data
     * @return array Formatted data ready for PDF generation
     */
    private function preparePDFData(ApplicationState $applicationState): array
    {
        $formData = $applicationState->form_data ?? [];
        $responses = $formData['formResponses'] ?? [];
        
        // Base data with metadata
        $data = [
            'applicationDate' => Carbon::now()->format('d/m/Y'),
            'applicationNumber' => $applicationState->application_number,
            'sessionId' => $applicationState->session_id,
            'referenceCode' => $formData['referenceCode'] ?? $applicationState->resume_code ?? '',
            'generatedAt' => Carbon::now()->format('Y-m-d H:i:s'),
            'metadata' => $applicationState->metadata ?? [],
            'checkType' => $applicationState->check_type ?? '',
            'checkStatus' => $applicationState->check_status ?? '',
            'checkResult' => $applicationState->check_result ?? [],
        ];
        
        // Form responses - use deep merge to handle nested arrays properly
        $data = $this->deepMerge($data, $responses);
        
        // Format and enhance product selection data
        // Format and enhance product selection data
        $lineItems = [];
        $productDescription = '';
        $itemsTotal = 0;

        // Check for Shopping Cart (Construction / Agro Inputs)
        if (isset($formData['cart']) && is_array($formData['cart']) && count($formData['cart']) > 0) {
            $productDescription = "Itemized Order List:\n";
            
            foreach ($formData['cart'] as $cartItem) {
                // For cart items (Construction/Agro), businessId maps to Product ID
                $product = Product::find($cartItem['businessId'] ?? 0);
                
                $name = $cartItem['name'] ?? 'Unknown Item';
                $qty = $cartItem['quantity'] ?? 1;
                $desc = '';
                
                if ($product) {
                    $desc = $product->specification ?? $product->name;
                    // Append supplier if available
                    if ($product->supplier) {
                        $desc .= " (Supplier: {$product->supplier->name})";
                    }
                }
                
                $lineItems[] = [
                    'name' => $name,
                    'quantity' => $qty,
                    'code' => $product->product_code ?? '',
                    'specification' => $desc,
                    'is_package' => false
                ];
                
                $productDescription .= "- {$name} (x{$qty})\n";
            }
            
            $data['productName'] = "Combined Order (" . count($formData['cart']) . " items)";
            
        } elseif (isset($formData['selectedBusiness'])) {
            // Single Item Selection
            $data['productName'] = $formData['selectedBusiness']['name'] ?? '';
            $data['productTotal'] = $this->formatCurrency($formData['finalPrice'] ?? 0);
            $data['productScale'] = $formData['selectedScale']['name'] ?? '';
            
            // Log logic for strict debugging
            $intent = $formData['intent'] ?? $applicationState->channel ?? '';
            
            // MicroBiz or Service Logic
            if ($intent === 'microBiz' || 
                in_array($formData['subcategory'] ?? '', ['License Courses', 'Driving School', 'Zimparks', 'School Fees']) ||
                str_contains(strtolower($intent), 'service')) {
                
                $packageId = $formData['selectedScale']['id'] ?? $formData['scaleId'] ?? null;
                $package = MicrobizPackage::with(['items', 'subcategory'])->find($packageId);
                
                if ($package) {
                    $productDescription = $package->generated_description;
                    
                    // Add items for table display
                    foreach ($package->items as $item) {
                        $lineItems[] = [
                            'name' => $item->name,
                            'quantity' => $item->pivot->quantity ?? 1,
                            'code' => $item->product_code ?? '',
                            'specification' => $item->specification ?? '', // Add spec if available
                            'is_package' => true
                        ];
                    }
                } else {
                    // Fallback if package not found (using frontend data)
                     $productDescription = $formData['selectedBusiness']['description'] ?? '';
                }
                
            } else {
                // Personal Products / Hire Purchase Logic
                $productId = $formData['selectedBusiness']['id'] ?? $formData['productId'] ?? null;
                $product = Product::with('supplier')->find($productId);
                
                if ($product) {
                    $productDescription = $product->generated_description;
                    
                     $lineItems[] = [
                        'name' => $product->name,
                        'quantity' => 1, // Usually 1 for single selection
                        'code' => $product->product_code ?? '',
                        'specification' => $product->specification ?? '',
                        'is_package' => false
                    ];
                } else {
                     $productDescription = $formData['selectedBusiness']['description'] ?? '';
                }
            }
        }
        
        $data['productDescription'] = $productDescription;
        $data['lineItems'] = $lineItems;
        $data['productAmount'] = $this->formatCurrency($formData['finalPrice'] ?? 0);
        $data['productScaleDescription'] = $formData['selectedScale']['description'] ?? '';
        
        // Add employer specific fields with enhanced information
        $employer = $formData['employer'] ?? '';
        $employerCategory = $formData['employerCategory'] ?? '';
        
        // Enhanced employer information
        $data['employerInfo'] = [
            'code' => $employer,
            'category' => $employerCategory,
            'name' => $this->getEmployerName($employer),
            'type' => $this->getEmployerType($employer),
            'fullDetails' => $this->getEmployerName($employer) . ' (' . $this->getEmployerType($employer) . ')'
        ];
        
        // Add simplified employer fields for backward compatibility
        $data['employerName'] = $data['employerInfo']['name'];
        $data['employerType'] = $data['employerInfo']['type'];
        $data['employerCategory'] = $data['employerInfo']['category'];
        
        // Add guarantor info if present in root formData (Account Holders Form structure)
        if (isset($formData['guarantor'])) {
            $data['guarantor'] = $formData['guarantor'];
        }
        
        // Add formResponses as a separate variable for template compatibility
        // Determine template type to provide appropriate defaults
        $hasAccount = $formData['hasAccount'] ?? false;
        
        // Consistent logic with generateApplicationPDF
        if (($formData['wantsAccount'] ?? false) === true) {
            $hasAccount = false;
        }
        
        $template = $this->determineTemplate($formData['employer'] ?? 'private', $hasAccount);
        $defaultFields = $this->getDefaultFieldsForTemplate($template);
        
        // Merge defaults with actual responses (recursive to handle nested arrays)
        $mergedResponses = array_replace_recursive($defaultFields, $responses);
        
        // DELIVERY ADDRESS OVERRIDE
        // If the user selected a delivery depot, we override the address fields to show the collection point
        if (isset($formData['deliverySelection']) && !empty($formData['deliverySelection'])) {
            $delivery = $formData['deliverySelection'];
            $agent = $delivery['agent'] ?? '';
            $city = $delivery['city'] ?? '';
            $depot = $delivery['depot'] ?? '';
            
            $collectionAddress = "COLLECTION AT: {$agent}";
            if ($depot) {
                $collectionAddress .= " - {$depot}";
            }
            if ($city && !str_contains($depot, $city)) {
                 $collectionAddress .= ", {$city}";
            }
            
            // Do NOT overwrite residential address. Use a specific delivery address field.
            $data['deliveryAddress'] = $collectionAddress;
            
            // Log this
            Log::info("Prepared delivery address for PDF", [
                'session_id' => $applicationState->session_id,
                'delivery_address' => $collectionAddress
            ]);
        } else {
            $data['deliveryAddress'] = '';
        }
        
        // Clean up any unexpected nested arrays (except for known nested structures)
        $knownArrayFields = [
            'declaration', 'spouseDetails', 'otherLoans', 'nextOfKin', 'funeralCover', 
            'capitalSources', 'customerBase', 'servicesRequired', 'employerType',
            'guarantor'
        ];
        foreach ($mergedResponses as $key => &$value) {
            if (!in_array($key, $knownArrayFields) && is_array($value)) {
                // Log what we're converting
                \Log::warning('Converting array to string in formResponses', [
                    'key' => $key,
                    'value' => $value
                ]);
                // Convert unexpected arrays to empty strings
                $value = '';
            }
        }
        
        // Also check the main $data array for any arrays that shouldn't be there
        foreach ($data as $key => &$value) {
            if (!in_array($key, ['employerInfo', 'creditFacility', 'applicationStatus', 'adminProcessing']) && is_array($value) && !isset($value[0])) {
                // This is an associative array that might cause issues
                \Log::warning('Found unexpected array in main data', [
                    'key' => $key,
                    'value' => $value
                ]);
            }
        }
        
        // Auto-populate declaration if empty
        if (empty($mergedResponses['declaration']['fullName']) && !empty($mergedResponses['firstName'])) {
            $mergedResponses['declaration']['fullName'] = trim($mergedResponses['firstName'] . ' ' . $mergedResponses['surname']);
        }
        if (empty($mergedResponses['declaration']['date'])) {
            $mergedResponses['declaration']['date'] = date('Y-m-d');
        }
        
        $data['formResponses'] = $mergedResponses;
        
        // Format all date fields consistently
        $data = $this->formatDateFields($data);
        
        // Format all currency fields consistently
        $data = $this->formatCurrencyFields($data);
        
        // Format all phone numbers consistently
        $data = $this->formatPhoneFields($data);
        
        // Format all ID numbers consistently
        $data = $this->formatIdFields($data);
        
        // Process documents from formData
        if (isset($formData['documents'])) {
            \Log::info('Preparing PDF Data - Documents found', [
                'session_id' => $applicationState->session_id,
                'keys' => array_keys($formData['documents']),
                'has_refs' => isset($formData['documents']['documentReferences']),
                'refs_preview' => isset($formData['documents']['documentReferences']) ? substr(json_encode($formData['documents']['documentReferences']), 0, 500) : 'N/A'
            ]);

            $data['documents'] = $formData['documents'];
            $data['selfieImage'] = $formData['documents']['selfie'] ?? null;
            $data['signatureImage'] = $formData['documents']['signature'] ?? null;
            
            // Map documentReferences to documentsByType if not already present
            if (!isset($data['documentsByType']) && isset($formData['documents']['documentReferences'])) {
                $data['documentsByType'] = $formData['documents']['documentReferences'];
            }
            
            // If documentsByType is still not set, check uploadedDocuments
            if (!isset($data['documentsByType']) && isset($formData['documents']['uploadedDocuments'])) {
                $data['documentsByType'] = $formData['documents']['uploadedDocuments'];
            }

            // Normalize documentsByType to ensure it's an array of arrays
            if (isset($data['documentsByType']) && is_array($data['documentsByType'])) {
                foreach ($data['documentsByType'] as $type => &$docs) {
                    // Handle case where specific type is just a string (single path)
                    if (is_string($docs)) {
                        $docs = [['path' => $docs, 'name' => basename($docs), 'type' => 'application/octet-stream', 'size' => 0]];
                    }
                    // Handle case where specific type is a single object (not array of objects)
                    elseif (is_array($docs) && isset($docs['path'])) {
                        $docs = [$docs];
                    }
                    // Handle case where it's an array of strings
                    elseif (is_array($docs)) {
                        foreach ($docs as $k => &$doc) {
                            if (is_string($doc)) {
                                $doc = ['path' => $doc, 'name' => basename($doc), 'type' => 'application/octet-stream', 'size' => 0];
                            }
                        }
                    }
                }
            }
        }
        
        // Enhanced credit facility details
        $data['creditFacility'] = [
            'type' => $responses['creditFacilityType'] ?? 'N/A',
            'term' => $responses['loanTenure'] ?? '12',
            'termUnit' => 'months',
            'monthlyPayment' => $this->formatCurrency($responses['monthlyPayment'] ?? 0),
            'interestRate' => $this->formatPercentage($responses['interestRate'] ?? 10),
            'totalInterest' => $this->calculateTotalInterest(
                $responses['loanAmount'] ?? 0, 
                $responses['interestRate'] ?? 10, 
                $responses['loanTenure'] ?? 12
            ),
            'totalRepayment' => $this->calculateTotalRepayment(
                $responses['loanAmount'] ?? 0, 
                $responses['interestRate'] ?? 10, 
                $responses['loanTenure'] ?? 12
            ),
            'purpose' => $responses['purposeOfLoan'] ?? $responses['loanPurpose'] ?? 'N/A',
        ];
        
        // Add simplified credit facility fields for backward compatibility
        $data['creditFacilityType'] = $data['creditFacility']['type'];
        $data['loanTerm'] = $data['creditFacility']['term'];
        $data['monthlyPayment'] = $data['creditFacility']['monthlyPayment'];
        $data['interestRate'] = $data['creditFacility']['interestRate'];
        
        // Format loan amount properly
        if (isset($responses['loanAmount'])) {
            $data['productAmount'] = $this->formatCurrency($responses['loanAmount']);
            $data['loanAmount'] = $this->formatCurrency($responses['loanAmount']);
        }
        
        // Enhanced document handling
        // IMPORTANT: Always define these variables to prevent "Undefined variable" errors in templates
        // Templates use these in closure `use` clauses which require the variables to exist
        $data['documents'] = [];
        $data['selfieImage'] = '';
        $data['signatureImage'] = '';
        $data['documentsUploadedAt'] = '';
        $data['hasDocuments'] = false;
        $data['documentsByType'] = [];

        if (isset($formData['documents'])) {
            $data['hasDocuments'] = true;
            $data['documents'] = $formData['documents'];

            // Process selfie and signature for embedding
            $data['selfieImage'] = $formData['documents']['selfie'] ?? '';
            $data['signatureImage'] = $formData['documents']['signature'] ?? '';
            $data['documentsUploadedAt'] = $formData['documents']['uploadedAt'] ?? '';

            // Process uploaded documents by type
            // Check both uploadedDocuments and documentReferences for document paths
            $data['documentsByType'] = [];

            // First try documentReferences (preferred - contains actual file paths)
            if (isset($formData['documents']['documentReferences']) && !empty($formData['documents']['documentReferences'])) {
                foreach ($formData['documents']['documentReferences'] as $type => $docs) {
                    if (is_array($docs) && count($docs) > 0) {
                        $data['documentsByType'][$type] = array_map(function($doc) {
                            return [
                                'name' => $doc['name'] ?? 'Document',
                                'path' => $doc['path'] ?? '',
                                'type' => $doc['type'] ?? 'application/octet-stream',
                                'size' => $this->formatFileSize($doc['size'] ?? 0),
                                'uploadedAt' => $doc['uploadedAt'] ?? $doc['uploaded_at'] ?? now()->toIsoString(),
                                'embeddedData' => null // Will be populated later
                            ];
                        }, $docs);
                    }
                }
            }
            // Fallback to uploadedDocuments if documentReferences is empty, but be careful of raw File objects
            elseif (isset($formData['documents']['uploadedDocuments'])) {
                foreach ($formData['documents']['uploadedDocuments'] as $type => $docs) {
                    if (is_array($docs) && count($docs) > 0) {
                        $data['documentsByType'][$type] = array_map(function($doc) {
                            // Skip if it's a raw File object (shouldn't happen on backend but good to be safe)
                            if (is_object($doc) && get_class($doc) === 'Illuminate\Http\UploadedFile') {
                                return null;
                            }
                            return [
                                'name' => $doc['name'] ?? 'Document',
                                'path' => $doc['path'] ?? '',
                                'type' => $doc['type'] ?? 'application/octet-stream',
                                'size' => $this->formatFileSize($doc['size'] ?? 0),
                                'uploadedAt' => $doc['uploadedAt'] ?? now()->toIsoString(),
                            ];
                        }, $docs);
                        // Filter out nulls
                        $data['documentsByType'][$type] = array_filter($data['documentsByType'][$type]);
                    }
                }
            }
        } else {
            $data['hasDocuments'] = false;
        }
        
        // Add application status information
        $data['applicationStatus'] = [
            'current' => $formData['status'] ?? 'pending',
            'updatedAt' => $formData['statusUpdatedAt'] ?? Carbon::now()->format('Y-m-d H:i:s'),
            'history' => $formData['statusHistory'] ?? [],
        ];
        
        // Add platform information
        $data['platform'] = $formData['platform'] ?? 'web';
        $data['completedSteps'] = $formData['completedSteps'] ?? [];
        
        // Add WhatsApp integration data if available
        if (isset($formData['whatsappNumber'])) {
            $data['whatsapp'] = [
                'number' => $formData['whatsappNumber'],
                'sessionId' => $formData['whatsappSessionId'] ?? '',
                'lastMessageAt' => $formData['whatsappLastMessageAt'] ?? '',
            ];
        }
        
        // Add admin processing data if available
        if (isset($formData['assignedTo'])) {
            $data['adminProcessing'] = [
                'assignedTo' => $formData['assignedTo'],
                'notes' => $formData['processingNotes'] ?? '',
                'approvalStatus' => $formData['approvalStatus'] ?? 'pending',
                'approvalDate' => $formData['approvalDate'] ?? '',
                'approvedBy' => $formData['approvedBy'] ?? '',
                'rejectionReason' => $formData['rejectionReason'] ?? '',
            ];
        }
        
        // Merge formResponses into root level for template access (excluding nested arrays)
        if (isset($data['formResponses']) && is_array($data['formResponses'])) {
            $knownNestedFields = ['declaration', 'spouseDetails', 'otherLoans', 'nextOfKin', 'funeralCover', 'capitalSources', 'customerBase', 'servicesRequired', 'employerType'];
            foreach ($data['formResponses'] as $key => $value) {
                // Only merge simple values to avoid overwriting complex structures
                if (!is_array($value) || in_array($key, $knownNestedFields)) {
                    $data[$key] = $value;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Format currency values consistently
     * 
     * @param mixed $value The value to format
     * @param string $currency The currency code (default: USD)
     * @return string Formatted currency string
     */
    private function formatCurrency($value, string $currency = 'USD'): string
    {
        if (empty($value)) return '0.00';
        
        // Remove any non-numeric characters except decimal point
        $value = preg_replace('/[^0-9.]/', '', (string)$value);
        
        // Format with 2 decimal places
        return number_format((float)$value, 2, '.', ',');
    }
    
    /**
     * Format percentage values consistently
     * 
     * @param mixed $value The value to format
     * @return string Formatted percentage string
     */
    private function formatPercentage($value): string
    {
        if (empty($value)) return '0.00%';
        
        // Remove any non-numeric characters except decimal point
        $value = preg_replace('/[^0-9.]/', '', (string)$value);
        
        // Format with 2 decimal places and add % symbol
        return number_format((float)$value, 2, '.', ',') . '%';
    }
    
    /**
     * Format all date fields in the data array
     * 
     * @param array $data The data array containing date fields
     * @return array Updated data array with formatted dates
     */
    private function formatDateFields(array $data): array
    {
        $dateFields = [
            'dateOfBirth', 'passportExpiry', 'dateOfEmployment', 'applicationDate',
            'documentsUploadedAt', 'statusUpdatedAt', 'lastInteractionAt', 'whatsappLastMessageAt',
            'approvalDate', 'incorporationDate', 'maturityDate'
        ];
        
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                try {
                    $date = new Carbon($data[$field]);
                    $data[$field] = $date->format('d/m/Y');
                    // Also provide alternative formats
                    $data[$field.'_formatted'] = [
                        'dmy' => $date->format('d/m/Y'),
                        'ymd' => $date->format('Y-m-d'),
                        'full' => $date->format('j F Y'),
                        'timestamp' => $date->timestamp,
                    ];
                } catch (\Exception $e) {
                    // Keep original value if parsing fails
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Format all currency fields in the data array
     * 
     * @param array $data The data array containing currency fields
     * @return array Updated data array with formatted currency values
     */
    private function formatCurrencyFields(array $data): array
    {
        $currencyFields = [
            'loanAmount', 'monthlyPayment', 'currentNetSalary', 'grossMonthlySalary',
            'netSalary', 'otherIncome', 'businessAnnualRevenue', 'initialCapital',
            'estimatedAnnualSales', 'netProfit', 'totalLiabilities', 'netCashFlow'
        ];
        
        foreach ($currencyFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->formatCurrency($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Format all phone number fields in the data array
     * 
     * @param array $data The data array containing phone fields
     * @return array Updated data array with formatted phone numbers
     */
    private function formatPhoneFields(array $data): array
    {
        $phoneFields = [
            'mobile', 'phone', 'telephoneRes', 'bus', 'employerContact', 'spouseContact',
            'mobileMoneyNumber', 'eWalletNumber', 'businessPhone', 'contactPhone'
        ];
        
        foreach ($phoneFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->formatPhoneNumber($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Format all ID number fields in the data array
     * 
     * @param array $data The data array containing ID fields
     * @return array Updated data array with formatted ID numbers
     */
    private function formatIdFields(array $data): array
    {
        $idFields = ['nationalIdNumber', 'spouseIdNumber'];
        
        foreach ($idFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                // Format ID number with proper dashes if needed
                $idNumber = preg_replace('/[^0-9A-Z]/', '', $data[$field]);
                if (strlen($idNumber) >= 10) {
                    $data[$field] = substr($idNumber, 0, 2) . '-' . 
                                   substr($idNumber, 2, 6) . '-' . 
                                   substr($idNumber, 8);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Format file size in human-readable format
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Calculate total interest for a loan
     * 
     * @param float $principal Loan principal amount
     * @param float $rate Annual interest rate (percentage)
     * @param int $term Loan term in months
     * @return string Formatted total interest
     */
    private function calculateTotalInterest($principal, $rate, $term): string
    {
        $principal = (float)$principal;
        $rate = (float)$rate / 100; // Convert percentage to decimal
        $term = (int)$term;
        
        // Simple interest calculation: P * r * t (where t is in years)
        $interest = $principal * $rate * ($term / 12);
        
        return $this->formatCurrency($interest);
    }
    
    /**
     * Calculate total repayment amount for a loan
     * 
     * @param float $principal Loan principal amount
     * @param float $rate Annual interest rate (percentage)
     * @param int $term Loan term in months
     * @return string Formatted total repayment
     */
    private function calculateTotalRepayment($principal, $rate, $term): string
    {
        $principal = (float)$principal;
        $rate = (float)$rate / 100; // Convert percentage to decimal
        $term = (int)$term;
        
        // Simple interest calculation: P + (P * r * t) where t is in years
        $totalRepayment = $principal + ($principal * $rate * ($term / 12));
        
        return $this->formatCurrency($totalRepayment);
    }
    
    /**
     * Get employer type from employer code
     * 
     * @param string $employerCode The employer code
     * @return string The employer type
     */
    private function getEmployerType(string $employerCode): string
    {
        $employerTypes = [
            'goz-ssb' => 'Government',
            'goz-zappa' => 'Government',
            'goz-pension' => 'Government',
            'town-council' => 'Local Authority',
            'parastatal' => 'Parastatal',
            'mission-private-schools' => 'Private',
            'entrepreneur' => 'Self-Employed',
            'large-corporate' => 'Corporate',
            'other' => 'Other'
        ];
        
        return $employerTypes[$employerCode] ?? 'Unknown';
    }
    
    /**
     * Deep merge two arrays recursively
     * 
     * @param array $array1 First array
     * @param array $array2 Second array
     * @return array Merged array
     */
    private function deepMerge(array $array1, array $array2): array
    {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->deepMerge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    

    
    /**
     * Generate filename for PDF
     */
    private function generateFilename(ApplicationState $applicationState): string
    {
        $formData = $applicationState->form_data ?? [];
        $responses = $formData['formResponses'] ?? [];
        
        $firstName = $responses['firstName'] ?? 'Unknown';
        $lastName = $responses['lastName'] ?? 'User';
        $date = Carbon::now()->format('Ymd');
        
        return "{$lastName}_{$firstName}_Application_{$date}.pdf";
    }
    
    /**
     * Get employer name from code
     */
    private function getEmployerName(string $employerCode): string
    {
        $employers = [
            'goz-ssb' => 'Government of Zimbabwe - SSB',
            'goz-zappa' => 'Government of Zimbabwe - ZAPPA',
            'goz-pension' => 'Government of Zimbabwe - Pension',
            'town-council' => 'Town Council',
            'parastatal' => 'Parastatal',
            'mission-private-schools' => 'Mission and Private Schools',
            'entrepreneur' => 'Entrepreneur',
            'large-corporate' => 'Large Corporate',
            'other' => 'Other'
        ];
        
        return $employers[$employerCode] ?? 'Unknown';
    }
    
    /**
     * Format phone number for display
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format as +263 77 123 4567
        if (strlen($phone) >= 9) {
            if (substr($phone, 0, 3) === '263') {
                return '+' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 2) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8);
            } elseif (substr($phone, 0, 1) === '0') {
                return $phone; // Local format
            }
        }
        
        return $phone;
    }
    
    /**
     * Calculate monthly payment (simple calculation)
     */
    private function calculateMonthlyPayment(float $amount, int $months = 12): float
    {
        if ($amount <= 0) {
            return 0;
        }
        
        // Simple 10% interest rate
        $interestRate = 0.10;
        $totalAmount = $amount * (1 + $interestRate);
        
        return round($totalAmount / $months, 2);
    }
    
    /**
     * Generate all PDFs for completed applications with optimized JIT processing
     * 
     * @param array $sessionIds Array of session IDs to generate PDFs for
     * @return array Results of batch generation
     */
    public function generateBatchPDFs(array $sessionIds): array
    {
        $results = [];
        $startTime = microtime(true);
        $totalApplications = count($sessionIds);
        $processedCount = 0;
        
        // Log batch generation start
        \Log::info("Starting batch PDF generation for {$totalApplications} applications");
        
        foreach ($sessionIds as $sessionId) {
            $applicationStartTime = microtime(true);
            
            // Find application state
            $state = ApplicationState::where('session_id', $sessionId)
                ->where('current_step', 'completed')
                ->first();
                
            if ($state) {
                try {
                    // Check if PDF already exists and is recent (within last hour)
                    $formData = $state->form_data ?? [];
                    $pdfPath = $formData['pdfPath'] ?? null;
                    $pdfGeneratedAt = $formData['pdfGeneratedAt'] ?? null;
                    
                    $shouldRegenerate = true;
                    
                    if ($pdfPath && $pdfGeneratedAt) {
                        $generatedTime = Carbon::parse($pdfGeneratedAt);
                        $hourAgo = Carbon::now()->subHour();
                        
                        // If PDF was generated within the last hour and file exists, don't regenerate
                        if ($generatedTime->isAfter($hourAgo) && Storage::disk('public')->exists($pdfPath)) {
                            $shouldRegenerate = false;
                            
                            $results[] = [
                                'session_id' => $sessionId,
                                'status' => 'success',
                                'path' => $pdfPath,
                                'cached' => true,
                                'message' => 'Using cached PDF (generated at ' . $generatedTime->format('Y-m-d H:i:s') . ')'
                            ];
                        }
                    }
                    
                    if ($shouldRegenerate) {
                        // Generate new PDF
                        $path = $this->generateApplicationPDF($state);
                        
                        $results[] = [
                            'session_id' => $sessionId,
                            'status' => 'success',
                            'path' => $path,
                            'cached' => false,
                            'processingTime' => round(microtime(true) - $applicationStartTime, 2) . 's'
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::error("Error generating PDF for session {$sessionId}: " . $e->getMessage());
                    
                    $results[] = [
                        'session_id' => $sessionId,
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ];
                }
            } else {
                $results[] = [
                    'session_id' => $sessionId,
                    'status' => 'error',
                    'message' => 'Application not found or not completed'
                ];
            }
            
            $processedCount++;
            
            // Log progress for large batches
            if ($totalApplications > 10 && $processedCount % 10 === 0) {
                \Log::info("Batch PDF generation progress: {$processedCount}/{$totalApplications}");
            }
        }
        
        $totalTime = round(microtime(true) - $startTime, 2);
        $averageTime = $processedCount > 0 ? round($totalTime / $processedCount, 2) : 0;
        
        // Add batch summary
        $batchSummary = [
            'totalApplications' => $totalApplications,
            'successCount' => count(array_filter($results, function($r) { return $r['status'] === 'success'; })),
            'errorCount' => count(array_filter($results, function($r) { return $r['status'] === 'error'; })),
            'cachedCount' => count(array_filter($results, function($r) { return isset($r['cached']) && $r['cached']; })),
            'totalProcessingTime' => $totalTime . 's',
            'averageProcessingTime' => $averageTime . 's',
            'completedAt' => Carbon::now()->format('Y-m-d H:i:s')
        ];
        
        // Log batch completion
        \Log::info("Completed batch PDF generation", $batchSummary);
        
        // Add summary to results
        $results['summary'] = $batchSummary;

        return $results;
    }

    /**
     * Generate PDF for application state (interface implementation)
     */
    public function generatePDF(ApplicationState $applicationState, array $options = []): array
    {
        // Skip validation for admin operations or if explicitly requested
        $skipValidation = $options['skipValidation'] ?? false;
        $isAdmin = $options['admin'] ?? true; // Default to true for admin context
        
        if (!$skipValidation && !$isAdmin) {
            // Use validation service
            $validationErrors = $this->validationService->validateApplicationState($applicationState);
            if (!empty($validationErrors)) {
                throw new PDFIncompleteDataException(
                    'Application state validation failed: ' . implode(', ', $validationErrors),
                    ['validation_errors' => $validationErrors]
                );
            }
        }

        // Generate PDF using existing method
        $pdfPath = $this->generateApplicationPDF($applicationState);

        // Store PDF using storage service
        $pdfContent = Storage::disk('public')->get($pdfPath);
        $metadata = $this->metadataService->generateGenerationMetadata($applicationState, $options);
        $fileInfo = $this->storageService->storePDF($pdfContent, $applicationState, $metadata);

        return $fileInfo;
    }

    /**
     * Generate PDF from form data (interface implementation)
     */
    public function generateFromFormData(array $formData, string $formId, array $options = []): array
    {
        // Create temporary application state
        $tempApplicationState = new ApplicationState([
            'session_id' => 'temp_' . uniqid(),
            'channel' => 'api',
            'user_identifier' => 'temp_user',
            'current_step' => 'completed',
            'form_data' => array_merge($formData, ['formId' => $formId]),
        ]);

        return $this->generatePDF($tempApplicationState, $options);
    }

    /**
     * Validate application state for PDF generation (interface implementation)
     */
    public function validateForGeneration(ApplicationState $applicationState): array
    {
        $errors = [];

        // Use validation service
        $stateErrors = $this->validationService->validateApplicationState($applicationState);
        $errors = array_merge($errors, $stateErrors);

        // Validate form data
        $formData = $applicationState->form_data ?? [];
        $formId = $formData['formId'] ?? '';
        $formErrors = $this->validationService->validateFormData($formData, $formId);
        $errors = array_merge($errors, $formErrors);

        // Validate environment
        $envErrors = $this->validationService->validatePDFEnvironment();
        $errors = array_merge($errors, $envErrors);

        return $errors;
    }

    /**
     * Get supported form types (interface implementation)
     */
    public function getSupportedFormTypes(): array
    {
        return [
            'account_holder_loan_application.json' => 'Account Holder Loan Application',
            'ssb_account_opening_form.json' => 'SSB Account Opening',
            'individual_account_opening.json' => 'Individual Account Opening',
            'smes_business_account_opening.json' => 'SME Business Account Opening',
            'pensioners_loan_account.json' => 'Pensioners Loan Account',
        ];
    }

    /**
     * Generate PDF for account opening
     *
     * @param \App\Models\AccountOpening $accountOpening
     * @return string Path to generated PDF
     */
    public function generateAccountOpeningPDF(\App\Models\AccountOpening $accountOpening): string
    {
        try {
            $pdfData = [
                'reference_code' => $accountOpening->reference_code,
                'form_data' => $accountOpening->form_data,
                'formResponses' => $accountOpening->form_data['formResponses'] ?? [],
                'status' => $accountOpening->status,
                'created_at' => $accountOpening->created_at,
                'zb_account_number' => $accountOpening->zb_account_number,
            ];
            
            $pdf = PDF::loadView('forms.zb_account_opening_pdf', $pdfData);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = "account_opening_{$accountOpening->reference_code}_" . time() . ".pdf";
            $path = "account_openings/{$filename}";
            
            if (!Storage::disk('public')->exists('account_openings')) {
                Storage::disk('public')->makeDirectory('account_openings');
            }
            
            Storage::disk('public')->put($path, $pdf->output());
            
            Log::info('Account opening PDF generated', [
                'id' => $accountOpening->id,
                'path' => $path,
            ]);
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Account opening PDF generation failed', [
                'id' => $accountOpening->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
