<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\ZimbabweanIDValidator;

class DocumentUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxFileSize = (int) env('MAX_FILE_SIZE', 10240); // KB
        $allowedTypes = explode(',', env('ALLOWED_FILE_TYPES', 'pdf,jpg,jpeg,png'));
        
        return [
            'file' => [
                'required',
                'file',
                "mimes:" . implode(',', $allowedTypes),
                "max:{$maxFileSize}",
                function ($attribute, $value, $fail) {
                    // Additional file validation
                    if ($value) {
                        // Check file size in bytes (additional check)
                        $maxBytes = (int) env('MAX_FILE_SIZE', 10240) * 1024;
                        if ($value->getSize() > $maxBytes) {
                            $fail("The {$attribute} must not be larger than " . ($maxBytes / 1024 / 1024) . "MB.");
                        }
                        
                        // Check for suspicious file content
                        $this->validateFileContent($value, $fail);
                    }
                },
            ],
            'document_type' => [
                'required',
                'string',
                Rule::in([
                    'national_id',
                    'passport',
                    'drivers_license',
                    'proof_of_residence',
                    'payslip',
                    'bank_statement',
                    'employment_letter',
                    'selfie',
                    'signature',
                    'other'
                ]),
            ],
            'session_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'national_id_number' => [
                'required_if:document_type,national_id',
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    // Only validate if document type is national_id and value is provided
                    if ($this->document_type === 'national_id' && !empty($value)) {
                        $validation = ZimbabweanIDValidator::validate($value);
                        if (!$validation['valid']) {
                            $fail($validation['message']);
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'Only PDF, JPG, JPEG, and PNG files are allowed.',
            'file.max' => 'File size must not exceed ' . (env('MAX_FILE_SIZE', 10240) / 1024) . 'MB.',
            'document_type.required' => 'Please specify the document type.',
            'document_type.in' => 'Invalid document type selected.',
            'session_id.required' => 'Session ID is required.',
            'session_id.regex' => 'Session ID contains invalid characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'session_id' => $this->sanitizeString($this->session_id),
            'document_type' => $this->sanitizeString($this->document_type),
            'description' => $this->sanitizeString($this->description),
        ]);
    }

    /**
     * Validate file content for security
     */
    private function validateFileContent($file, $fail): void
    {
        $filePath = $file->getRealPath();
        $mimeType = $file->getMimeType();
        
        // Read first few bytes to check for malicious content
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            $header = fread($handle, 1024);
            fclose($handle);
            
            // Check for script tags in any file type
            if (stripos($header, '<script') !== false || 
                stripos($header, '<?php') !== false ||
                stripos($header, '<%') !== false) {
                $fail('File contains potentially malicious content.');
                return;
            }
            
            // Validate file signatures
            $this->validateFileSignature($header, $mimeType, $fail);
        }
    }

    /**
     * Validate file signature matches declared MIME type
     */
    private function validateFileSignature(string $header, ?string $mimeType, $fail): void
    {
        $signatures = [
            'image/jpeg' => ["\xFF\xD8\xFF"],
            'image/png' => ["\x89\x50\x4E\x47"],
            'application/pdf' => ["%PDF-"],
        ];

        if (!$mimeType || !isset($signatures[$mimeType])) {
            return; // Skip validation for unknown types
        }

        $validSignature = false;
        foreach ($signatures[$mimeType] as $signature) {
            if (str_starts_with($header, $signature)) {
                $validSignature = true;
                break;
            }
        }

        if (!$validSignature) {
            $fail('File signature does not match the declared file type.');
        }
    }

    /**
     * Sanitize string input
     */
    private function sanitizeString(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }
        
        // Remove null bytes and control characters
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Trim whitespace
        return trim($sanitized);
    }
}
