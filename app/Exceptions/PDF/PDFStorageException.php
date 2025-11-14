<?php

namespace App\Exceptions\PDF;

/**
 * Exception thrown when PDF storage operations fail
 */
class PDFStorageException extends PDFException
{
    /**
     * Create a new PDF storage exception instance
     *
     * @param  string  $message  The exception message
     * @param  array  $context  Additional context information
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(
        string $message = 'Failed to store or retrieve PDF',
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 'PDF_STORAGE_FAILED', $context, $code, $previous);
    }
}
