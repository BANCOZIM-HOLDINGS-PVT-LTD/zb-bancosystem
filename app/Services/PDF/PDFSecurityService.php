<?php

namespace App\Services\PDF;

use App\Models\ApplicationState;
use App\Services\PDFLoggingService;
use Illuminate\Support\Facades\Log;

class PDFSecurityService
{
    private PDFLoggingService $logger;

    public function __construct(PDFLoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Apply security settings to PDF
     */
    public function applySecuritySettings($pdf, ApplicationState $applicationState): void
    {
        if (! $this->shouldApplySecurity()) {
            return;
        }

        $domPdf = $pdf->getDomPDF();

        // Generate secure passwords
        $openPassword = null; // No password required to open
        $permissionPassword = $this->generateSecurePassword(
            (int) env('PDF_OWNER_PASSWORD_LENGTH', 16),
            $applicationState->session_id
        );

        try {
            // Set encryption and permissions
            $domPdf->get_canvas()->get_cpdf()->setEncryption(
                $openPassword,
                $permissionPassword,
                $this->getPermissions(),
                1 // 128-bit encryption
            );

            $this->logger->logInfo('PDF security applied', [
                'session_id' => $applicationState->session_id,
                'encryption_level' => '128-bit',
                'permissions' => $this->getPermissions(),
            ]);

        } catch (\Exception $e) {
            $this->logger->logWarning('Failed to apply PDF security', [
                'session_id' => $applicationState->session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate a cryptographically secure password
     */
    public function generateSecurePassword(int $length, string $sessionId): string
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
        $sessionHash = hash('sha256', $sessionId.config('app.key').time());
        $sessionEntropy = substr($sessionHash, 0, 4);

        // Mix the random password with session entropy
        $finalPassword = substr($password, 0, $length - 4).$sessionEntropy;

        // Log password generation (without the actual password)
        $this->logger->logInfo('PDF password generated', [
            'session_id' => $sessionId,
            'password_length' => strlen($finalPassword),
            'entropy_added' => true,
        ]);

        return $finalPassword;
    }

    /**
     * Check if security should be applied
     */
    private function shouldApplySecurity(): bool
    {
        return env('PDF_ENCRYPTION_ENABLED', true);
    }

    /**
     * Get PDF permissions
     */
    private function getPermissions(): array
    {
        return [
            'print',        // Allow printing
            'copy',         // Allow copying content
            // 'modify',    // Disallow modification
            // 'annot-forms' // Disallow annotations and forms
        ];
    }

    /**
     * Add watermark to PDF
     */
    public function addWatermark($pdf, ApplicationState $applicationState, ?string $watermarkText = null): void
    {
        if (! env('PDF_WATERMARK_ENABLED', false)) {
            return;
        }

        $watermarkText = $watermarkText ?? $this->getDefaultWatermarkText($applicationState);

        try {
            // Get the canvas
            $canvas = $pdf->getDomPDF()->get_canvas();

            // Add watermark to each page
            $pageCount = $canvas->get_page_count();

            for ($i = 1; $i <= $pageCount; $i++) {
                $this->addWatermarkToPage($canvas, $i, $watermarkText);
            }

            $this->logger->logInfo('PDF watermark applied', [
                'session_id' => $applicationState->session_id,
                'pages' => $pageCount,
                'watermark_text' => $watermarkText,
            ]);

        } catch (\Exception $e) {
            $this->logger->logWarning('Failed to apply PDF watermark', [
                'session_id' => $applicationState->session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add watermark to a specific page
     */
    private function addWatermarkToPage($canvas, int $pageNumber, string $text): void
    {
        // Set watermark properties
        $canvas->page_text(
            300, // x position
            400, // y position
            $text,
            null, // font
            12,   // size
            [0.8, 0.8, 0.8], // color (light gray)
            0,    // word_space
            0,    // char_space
            -45   // angle (diagonal)
        );
    }

    /**
     * Get default watermark text
     */
    private function getDefaultWatermarkText(ApplicationState $applicationState): string
    {
        $referenceCode = $applicationState->reference_code ?? 'DRAFT';
        $date = $applicationState->created_at->format('Y-m-d');

        return "BANCOZIM - {$referenceCode} - {$date}";
    }

    /**
     * Validate PDF content for security issues
     */
    public function validatePdfContent(array $data): array
    {
        $issues = [];

        // Check for potentially dangerous content
        $dangerousPatterns = [
            '<script',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror=',
            'onclick=',
            '<?php',
            '<%',
        ];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                foreach ($dangerousPatterns as $pattern) {
                    if (stripos($value, $pattern) !== false) {
                        $issues[] = "Potentially dangerous content found in field '{$key}': {$pattern}";
                    }
                }
            }
        }

        // Check for excessively long content that might cause issues
        foreach ($data as $key => $value) {
            if (is_string($value) && strlen($value) > 10000) {
                $issues[] = "Field '{$key}' contains excessively long content (".strlen($value).' characters)';
            }
        }

        return $issues;
    }

    /**
     * Sanitize PDF data
     */
    public function sanitizePdfData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove potentially dangerous content
                $value = strip_tags($value, '<b><i><u><br><p><div><span>');

                // Remove null bytes and control characters
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

                // Limit length
                if (strlen($value) > 5000) {
                    $value = substr($value, 0, 5000).'...';
                }

                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePdfData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
