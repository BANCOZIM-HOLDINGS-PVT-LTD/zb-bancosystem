<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReferenceCodeRequest extends FormRequest
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
        $action = $this->route()->getActionMethod();
        
        switch ($action) {
            case 'validate':
                return $this->validateRules();
            case 'getState':
                return $this->getStateRules();
            case 'generate':
                return $this->generateRules();
            default:
                return [];
        }
    }

    /**
     * Rules for validating a reference code
     */
    private function validateRules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'min:5',
                'regex:/^[A-Z0-9]{5,}$/', // Minimum 5 uppercase alphanumeric characters (supports both 6-char codes and National IDs)
            ],
        ];
    }

    /**
     * Rules for getting state by reference code
     */
    private function getStateRules(): array
    {
        return [
            'reference_code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[A-Z0-9]{6}$/',
            ],
            'user_identifier' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9@._+-]+$/',
            ],
        ];
    }

    /**
     * Rules for generating a reference code
     */
    private function generateRules(): array
    {
        return [
            'session_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/', // International phone number format
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'National ID or reference code is required.',
            'code.min' => 'National ID or reference code must be at least 5 characters.',
            'code.regex' => 'National ID or reference code must contain only uppercase letters and numbers.',
            'reference_code.required' => 'Reference code is required.',
            'reference_code.size' => 'Reference code must be exactly 6 characters.',
            'reference_code.regex' => 'Reference code must contain only uppercase letters and numbers.',
            'session_id.required' => 'Session ID is required.',
            'session_id.regex' => 'Session ID contains invalid characters.',
            'phone_number.regex' => 'Invalid phone number format.',
            'user_identifier.regex' => 'User identifier contains invalid characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert code to uppercase and remove spaces/dashes (for National IDs)
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(str_replace([' ', '-'], '', $this->sanitizeString($this->code)))
            ]);
        }

        // Convert reference code to uppercase
        if ($this->has('reference_code')) {
            $this->merge([
                'reference_code' => strtoupper($this->sanitizeString($this->reference_code))
            ]);
        }

        // Sanitize other inputs
        $sanitized = [];

        if ($this->has('session_id')) {
            $sanitized['session_id'] = $this->sanitizeString($this->session_id);
        }

        if ($this->has('user_identifier')) {
            $sanitized['user_identifier'] = $this->sanitizeString($this->user_identifier);
        }

        if ($this->has('phone_number')) {
            $sanitized['phone_number'] = $this->sanitizePhoneNumber($this->phone_number);
        }

        if ($this->has('email')) {
            $sanitized['email'] = $this->sanitizeString($this->email);
        }

        if (!empty($sanitized)) {
            $this->merge($sanitized);
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

    /**
     * Sanitize phone number input
     */
    private function sanitizePhoneNumber(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }
        
        // Remove all non-digit characters except + at the beginning
        $sanitized = preg_replace('/[^\d+]/', '', $input);
        
        // Ensure + is only at the beginning
        if (str_contains($sanitized, '+')) {
            $sanitized = '+' . str_replace('+', '', $sanitized);
        }
        
        return $sanitized;
    }
}
