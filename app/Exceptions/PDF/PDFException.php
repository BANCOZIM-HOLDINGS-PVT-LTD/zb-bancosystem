<?php

namespace App\Exceptions\PDF;

use Exception;

/**
 * Base exception class for PDF operations
 */
class PDFException extends Exception
{
    /**
     * Additional context information about the exception
     *
     * @var array
     */
    protected array $context;

    /**
     * Error code for the exception
     *
     * @var string
     */
    protected string $errorCode;

    /**
     * Create a new PDF exception instance
     *
     * @param string $message The exception message
     * @param string $errorCode The error code
     * @param array $context Additional context information
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(
        string $message = "PDF operation failed",
        string $errorCode = "PDF_ERROR",
        array $context = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Get the error code for the exception
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the context information for the exception
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Add additional context information to the exception
     *
     * @param string $key The context key
     * @param mixed $value The context value
     * @return $this
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Add multiple context values to the exception
     *
     * @param array $context The context array to add
     * @return $this
     */
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    /**
     * Get the exception as an array for API responses
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'error' => $this->getMessage(),
            'code' => $this->getErrorCode(),
            'details' => $this->getContext(),
        ];
    }
}