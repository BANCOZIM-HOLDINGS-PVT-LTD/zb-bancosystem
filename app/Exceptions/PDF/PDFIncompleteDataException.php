<?php

namespace App\Exceptions\PDF;

/**
 * Exception thrown when PDF generation is attempted with incomplete data
 */
class PDFIncompleteDataException extends PDFException
{
    /**
     * Create a new PDF incomplete data exception instance
     *
     * @param  string  $message  The exception message
     * @param  array  $context  Additional context information
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(
        string $message = 'Incomplete data for PDF generation',
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 'PDF_INCOMPLETE_DATA', $context, $code, $previous);
    }
}
