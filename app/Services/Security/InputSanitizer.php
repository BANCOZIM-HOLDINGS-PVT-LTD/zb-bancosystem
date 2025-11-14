<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;

class InputSanitizer
{
    /**
     * Dangerous patterns to detect and remove
     */
    private const DANGEROUS_PATTERNS = [
        // XSS patterns
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
        '/javascript:/i',
        '/vbscript:/i',
        '/onload\s*=/i',
        '/onerror\s*=/i',
        '/onclick\s*=/i',
        '/onmouseover\s*=/i',

        // SQL injection patterns
        '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION)\b)/i',
        '/(\b(OR|AND)\s+\d+\s*=\s*\d+)/i',
        '/(\b(OR|AND)\s+[\'"]?\w+[\'"]?\s*=\s*[\'"]?\w+[\'"]?)/i',

        // Command injection patterns
        '/(\||&|;|`|\$\(|\${)/i',
        '/\b(eval|exec|system|shell_exec|passthru|proc_open)\s*\(/i',

        // Path traversal patterns
        '/\.\.[\/\\]/i',
        '/\.(exe|bat|cmd|com|pif|scr|vbs|js)$/i',
    ];

    /**
     * Sanitize input data recursively
     */
    public function sanitize($data, array $options = []): mixed
    {
        if (is_array($data)) {
            return $this->sanitizeArray($data, $options);
        }

        if (is_string($data)) {
            return $this->sanitizeString($data, $options);
        }

        return $data;
    }

    /**
     * Sanitize array data recursively
     */
    private function sanitizeArray(array $data, array $options = []): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeString((string) $key, $options);
            $sanitized[$sanitizedKey] = $this->sanitize($value, $options);
        }

        return $sanitized;
    }

    /**
     * Sanitize string data
     */
    private function sanitizeString(string $data, array $options = []): string
    {
        // Skip sanitization for certain fields if specified
        if (isset($options['skip_fields']) && in_array($data, $options['skip_fields'])) {
            return $data;
        }

        $original = $data;

        // Remove null bytes
        $data = str_replace("\0", '', $data);

        // Normalize line endings
        $data = str_replace(["\r\n", "\r"], "\n", $data);

        // Remove dangerous patterns
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            $data = preg_replace($pattern, '', $data);
        }

        // HTML entity encoding for display contexts
        if ($options['html_encode'] ?? true) {
            $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Trim whitespace
        if ($options['trim'] ?? true) {
            $data = trim($data);
        }

        // Log if dangerous content was detected
        if ($original !== $data) {
            Log::warning('Potentially dangerous input detected and sanitized', [
                'original' => $original,
                'sanitized' => $data,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
            ]);
        }

        return $data;
    }

    /**
     * Validate and sanitize file uploads
     */
    public function sanitizeFileUpload(array $fileData): array
    {
        $sanitized = [];

        // Sanitize filename
        if (isset($fileData['name'])) {
            $sanitized['name'] = $this->sanitizeFilename($fileData['name']);
        }

        // Validate file type
        if (isset($fileData['type'])) {
            $sanitized['type'] = $this->validateMimeType($fileData['type']);
        }

        // Validate file size
        if (isset($fileData['size'])) {
            $sanitized['size'] = $this->validateFileSize($fileData['size']);
        }

        // Copy other safe fields
        $safeFields = ['tmp_name', 'error'];
        foreach ($safeFields as $field) {
            if (isset($fileData[$field])) {
                $sanitized[$field] = $fileData[$field];
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove path information
        $filename = basename($filename);

        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Prevent hidden files
        $filename = ltrim($filename, '.');

        // Ensure reasonable length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250 - strlen($extension));
            $filename = $name.'.'.$extension;
        }

        return $filename;
    }

    /**
     * Validate MIME type
     */
    private function validateMimeType(string $mimeType): string
    {
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (! in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException('Invalid file type: '.$mimeType);
        }

        return $mimeType;
    }

    /**
     * Validate file size
     */
    private function validateFileSize(int $size): int
    {
        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($size > $maxSize) {
            throw new \InvalidArgumentException('File size too large: '.$size.' bytes');
        }

        return $size;
    }

    /**
     * Sanitize JSON data
     */
    public function sanitizeJson(string $json): string
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: '.json_last_error_msg());
        }

        $sanitized = $this->sanitize($data);

        return json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Sanitize email address
     */
    public function sanitizeEmail(string $email): string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address: '.$email);
        }

        return strtolower($email);
    }

    /**
     * Sanitize phone number
     */
    public function sanitizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Validate format (basic international format)
        if (! preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            throw new \InvalidArgumentException('Invalid phone number format: '.$phone);
        }

        return $phone;
    }

    /**
     * Sanitize URL
     */
    public function sanitizeUrl(string $url): string
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL: '.$url);
        }

        // Only allow HTTP and HTTPS
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'])) {
            throw new \InvalidArgumentException('Invalid URL scheme: '.$scheme);
        }

        return $url;
    }

    /**
     * Check if input contains suspicious patterns
     */
    public function containsSuspiciousContent(string $input): bool
    {
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate security report for input
     */
    public function generateSecurityReport(string $input): array
    {
        $report = [
            'is_safe' => true,
            'threats_detected' => [],
            'sanitized_length' => strlen($input),
            'original_length' => strlen($input),
        ];

        $original = $input;
        $sanitized = $this->sanitizeString($input);

        $report['sanitized_length'] = strlen($sanitized);
        $report['is_safe'] = $original === $sanitized;

        if (! $report['is_safe']) {
            foreach (self::DANGEROUS_PATTERNS as $pattern) {
                if (preg_match($pattern, $original)) {
                    $report['threats_detected'][] = $pattern;
                }
            }
        }

        return $report;
    }
}
