<?php

namespace App\Exceptions\PDF;

/**
 * Exception thrown when PDF generation fails
 */
class PDFGenerationException extends PDFException
{
    /**
     * Create a new PDF generation exception instance
     *
     * @param string $message The exception message
     * @param array $context Additional context information
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(
        string $message = "Failed to generate PDF",
        array $context = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, "PDF_GENERATION_FAILED", $context, $code, $previous);
    }
}