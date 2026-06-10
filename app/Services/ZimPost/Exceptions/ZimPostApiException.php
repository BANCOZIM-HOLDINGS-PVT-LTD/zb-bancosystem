<?php

namespace App\Services\ZimPost\Exceptions;

use RuntimeException;
use Throwable;

class ZimPostApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?string $field = null,
        public readonly ?string $hint = null,
        public readonly int $httpStatus = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    public function isNotFound(): bool
    {
        return $this->httpStatus === 404 || $this->errorCode === 'ERR_018';
    }

    public function isAuthError(): bool
    {
        return in_array($this->errorCode, ['ERR_012', 'ERR_013'], true) || $this->httpStatus === 401;
    }

    public function isRateLimited(): bool
    {
        return $this->errorCode === 'ERR_014' || $this->httpStatus === 429;
    }
}
