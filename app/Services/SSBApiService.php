<?php

namespace App\Services;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * SSB API Service
 * 
 * This service handles integration with the Salary Deductions Gateway API.
 * It manages authentication (JWT), deduction instructions, and status lookups.
 */
class SSBApiService
{
    protected string $baseUrl;
    protected array $config;

    public function __construct()
    {
        $this->baseUrl = config('services.ssb.api_url');
        $this->config = config('services.ssb');
    }

    /**
     * Get JWT token for authentication
     */
    protected function getToken(): ?string
    {
        return Cache::remember('ssb_api_token', 3500, function () {
            try {
                $response = Http::asForm()->post($this->baseUrl . '/connect/token', [
                    'client_id' => $this->config['client_id'],
                    'username' => $this->config['username'],
                    'password' => $this->config['password'],
                    'grant_type' => 'password',
                    'scope' => 'UNIPAY offline_access',
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('SSB API Token Request Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('SSB API Token Exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Submit a single loan deduction instruction
     */
    public function submitLoan(ApplicationState $application): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Authentication failed'];
        }

        $formData = $application->form_data ?? [];
        $responses = $formData['formResponses'] ?? [];

        $payload = [
            'employerTIN' => $this->config['employer_tin'],
            'serviceProviderNumber' => $this->config['service_provider_number'],
            'serviceProviderTIN' => $this->config['service_provider_tin'],
            'serviceProviderName' => $this->config['service_provider_name'],
            'employeeNumber' => $responses['employmentNumber'] ?? $responses['ecNumber'] ?? '',
            'firstName' => $responses['firstName'] ?? '',
            'surname' => $responses['surname'] ?? '',
            'mobileNumber' => $responses['mobile'] ?? '',
            'monthlyAmount' => $formData['monthlyPayment'] ?? 0,
            'instalments' => $formData['creditTerm'] ?? 12,
            'loanRefenceNumber' => $application->reference_code,
        ];

        try {
            $response = Http::withToken($token)
                ->post($this->baseUrl . '/api/app/payroll/approve-loan', $payload);

            if ($response->successful()) {
                Log::info('SSB Loan Submitted Successfully', ['ref' => $application->reference_code]);
                return [
                    'success' => true,
                    'message' => $response->json('message'),
                    'api_response' => $response->json()
                ];
            }

            Log::error('SSB Loan Submission Failed', [
                'ref' => $application->reference_code,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'API Submission failed: ' . $response->status(),
                'error' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('SSB Loan Submission Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check loan status from SSB
     */
    public function checkStatus(string $loanRefNo): array
    {
        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'status' => 'AUTH_ERROR'];
        }

        try {
            $response = Http::withToken($token)
                ->get($this->baseUrl . '/api/app/payroll/loan-status', [
                    'loanRefNo' => $loanRefNo
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json('status'), // APPROVED, REJECTED, PENDING
                    'message' => $response->json('message'),
                    'full_response' => $response->json()
                ];
            }

            return ['success' => false, 'message' => 'Failed to fetch status'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch payment deductions from SSB
     */
    public function fetchPayments(?string $lastUniqueNo = null): array
    {
        $token = $this->getToken();
        if (!$token) return [];

        try {
            $url = $this->baseUrl . '/api/app/payroll/payments/';
            $params = $lastUniqueNo ? ['lastUniqueNo' => $lastUniqueNo] : [];
            
            $response = Http::withToken($token)->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
