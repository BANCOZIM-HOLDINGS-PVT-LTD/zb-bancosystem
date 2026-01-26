<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveApplicationStateRequest extends FormRequest
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
        return [
            'session_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/', // Only alphanumeric, underscore, and dash
            ],
            'channel' => [
                'required',
                Rule::in(['web', 'whatsapp', 'ussd', 'mobile_app']),
            ],
            'user_identifier' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9@._+-]+$/', // Allow email format and phone numbers
            ],
            'current_step' => [
                'required',
                'string',
                'max:100', // Increased to allow for any step name
                // Note: Removed strict Rule::in to allow any step name - validation is relaxed
                // to prevent issues with new or dynamically named steps
            ],
            'form_data' => [
                'required',
                'array',
            ],
            'form_data.language' => [
                'nullable',
                'string',
                Rule::in(['en', 'sn', 'nd']),
            ],
            'form_data.intent' => [
                'nullable',
                'string',
                // All valid intent values from the wizard
                Rule::in([
                    'hirePurchase', 'microBiz', 'checkStatus', 'trackDelivery', 
                    'loan', 'account', 'personalServices', 'cashPurchase',
                    'ssbLoan', 'zbLoan', 'accountOpening', 'rdcLoan',
                    'houseConstruction', 'agentApplication', 'agentLogin',
                    // New intent values from welcome page
                    'homeConstruction', 'personalGadgets'
                ]),
            ],
            'form_data.employer' => [
                'nullable',
                'string',
                'max:255',
            ],
            'form_data.amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000000', // Maximum loan amount
            ],
            'form_data.formResponses' => [
                'nullable',
                'array',
            ],
            'form_data.formResponses.firstName' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\'-]+$/', // Only letters, spaces, apostrophes, hyphens
            ],
            'form_data.formResponses.lastName' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\'-]+$/',
            ],
            'form_data.formResponses.emailAddress' => [
                'nullable',
                'email',
                'max:255',
            ],
            'form_data.formResponses.mobile' => [
                'nullable',
                'string',
                'regex:/^(\\+263|0)?[0-9\\s\\-\\(\\)]{7,15}$/', // Zimbabwe phone number format
            ],
            'form_data.formResponses.nationalIdNumber' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9-]+$/',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
            'metadata.ip_address' => [
                'nullable',
                // Removed 'ip' validation - might be null
            ],
            'metadata.user_agent' => [
                'nullable',
                'string',
                'max:4096', // Increased for long user agents
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'session_id.regex' => 'Session ID contains invalid characters.',
            'user_identifier.regex' => 'User identifier contains invalid characters.',
            'current_step.in' => 'Invalid application step.',
            'form_data.formResponses.firstName.regex' => 'First name contains invalid characters.',
            'form_data.formResponses.lastName.regex' => 'Last name contains invalid characters.',
            'form_data.formResponses.mobile.regex' => 'Invalid phone number format. Please use a valid Zimbabwe phone number (e.g., 0771234567 or +263771234567).',
            'form_data.formResponses.nationalIdNumber.regex' => 'National ID contains invalid characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        \Log::info('Entering SaveApplicationStateRequest::prepareForValidation', [
            'has_form_data' => $this->has('form_data'),
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'unknown'
        ]);

        // Sanitize string inputs
        $this->merge([
            'session_id' => $this->sanitizeString($this->session_id),
            'user_identifier' => $this->sanitizeString($this->user_identifier),
            'current_step' => $this->sanitizeString($this->current_step),
        ]);

        // Sanitize form data if present
        if ($this->has('form_data') && is_array($this->form_data)) {
            $formData = $this->form_data;
            
            // Sanitize form responses
            if (isset($formData['formResponses']) && is_array($formData['formResponses'])) {
                foreach ($formData['formResponses'] as $key => $value) {
                    if (is_string($value)) {
                        $formData['formResponses'][$key] = $this->sanitizeString($value);
                    }
                }
            }
            
            $this->merge(['form_data' => $formData]);
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
     * Handle a failed validation attempt - logs errors for debugging.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        \Log::warning('SaveApplicationStateRequest validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input_keys' => array_keys($this->all()),
            'session_id' => $this->session_id ?? 'not_provided',
            'current_step' => $this->current_step ?? 'not_provided',
        ]);

        throw new \Illuminate\Validation\ValidationException($validator);
    }
}
