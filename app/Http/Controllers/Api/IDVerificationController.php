<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ZimbabweanIDValidator;
use Illuminate\Support\Facades\Http;

class IDVerificationController extends Controller
{
    /**
     * Verify a Zimbabwean National ID card using OCR, AI, and biometric validation
     *
     * This endpoint accepts an image of a Zimbabwean national ID card (metal or plastic)
     * and performs comprehensive verification using:
     * - OCR (Optical Character Recognition) to extract data
     * - AI validation to verify authenticity
     * - Biometric analysis (optional)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyIDCard(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'id_card_image' => 'required|image|mimes:jpeg,jpg,png|max:10240', // Max 10MB
                'country' => 'required|string|in:ZW',
                'document_type' => 'required|string|in:NATIONAL_ID'
            ]);

            $image = $request->file('id_card_image');

            // Call didit.me ID verification API
            $verificationResult = $this->callDiditVerification($image);

            if ($verificationResult['verified']) {
                return response()->json([
                    'success' => true,
                    'message' => 'ID card verified successfully',
                    'data' => $verificationResult
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $verificationResult['error'] ?? 'ID verification failed',
                    'data' => null
                ], 422);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('ID Verification Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during verification. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Call didit.me ID Verification API
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return array
     * @throws \Exception
     */
    private function callDiditVerification($image)
    {
        try {
            $apiKey = config('services.didit.api_key');
            $apiUrl = config('services.didit.api_url');

            if (empty($apiKey)) {
                throw new \Exception('Didit API key not configured');
            }

            Log::info('Calling didit.me ID verification API', [
                'api_url' => $apiUrl,
                'image_size' => $image->getSize(),
                'image_type' => $image->getMimeType()
            ]);

            // Call didit.me API using Laravel HTTP client
            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->attach(
                'front_image',
                file_get_contents($image->getRealPath()),
                $image->getClientOriginalName()
            )
            ->post("{$apiUrl}/v2/id-verification/");

            if (!$response->successful()) {
                Log::error('Didit API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                throw new \Exception('ID verification service returned error: ' . $response->status());
            }

            $result = $response->json();

            Log::info('Didit API response received', [
                'has_data' => isset($result['data']),
                'status' => $result['status'] ?? 'unknown'
            ]);

            // Parse didit.me response and map to our format
            return $this->parseDiditResponse($result);

        } catch (\Exception $e) {
            Log::error('Didit verification failed: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Parse didit.me API response and map to our standard format
     *
     * @param array $diditResponse
     * @return array
     */
    private function parseDiditResponse(array $diditResponse)
    {
        // Check if verification was successful
        $verified = isset($diditResponse['status']) &&
                   ($diditResponse['status'] === 'approved' || $diditResponse['status'] === 'success');

        if (!$verified) {
            return [
                'verified' => false,
                'error' => $diditResponse['message'] ?? 'Verification failed'
            ];
        }

        // Extract data from didit response
        // Note: Field names may vary - adjust based on actual didit.me response structure
        $data = $diditResponse['data'] ?? [];
        $document = $data['document'] ?? [];
        $extracted = $document['extracted_data'] ?? [];

        // Determine card type (metal/plastic) - may need adjustment based on API response
        $cardType = 'plastic'; // Default
        if (isset($document['document_type'])) {
            $docType = strtolower($document['document_type']);
            if (strpos($docType, 'metal') !== false || strpos($docType, 'biometric') !== false) {
                $cardType = 'metal';
            }
        }

        // Map extracted fields to our format
        $idNumber = $extracted['id_number'] ?? $extracted['document_number'] ?? null;
        $firstName = $extracted['first_name'] ?? $extracted['given_name'] ?? null;
        $lastName = $extracted['last_name'] ?? $extracted['surname'] ?? $extracted['family_name'] ?? null;
        $dateOfBirth = $extracted['date_of_birth'] ?? $extracted['dob'] ?? $extracted['birth_date'] ?? null;
        $address = $extracted['address'] ?? null;
        $expiryDate = $extracted['expiry_date'] ?? $extracted['expiration_date'] ?? null;

        // Get confidence score
        $confidence = $data['confidence_score'] ?? $document['confidence'] ?? 0.95;
        if ($confidence > 1) {
            $confidence = $confidence / 100; // Convert percentage to decimal
        }

        return [
            'verified' => true,
            'id_number' => $idNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $dateOfBirth,
            'card_type' => $cardType,
            'expiry_date' => $expiryDate,
            'address' => $address,
            'confidence' => $confidence,
            'ocr_raw' => $diditResponse,
            'extracted_fields' => $extracted,
            'biometric_match' => isset($data['face_match']) ? $data['face_match'] : null,
            'face_image_url' => $data['face_image'] ?? null
        ];
    }

    /**
     * Example method for calling Smile Identity SDK
     * Uncomment and modify when integrating with actual SDK
     */
    /*
    private function callSmileIdentitySDK($image)
    {
        // Example integration with Smile Identity
        $apiKey = config('services.smile_identity.api_key');
        $partnerId = config('services.smile_identity.partner_id');
        $endpoint = 'https://api.smileidentity.com/v2/verify_document';

        $client = new \GuzzleHttp\Client();

        $response = $client->post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'multipart/form-data'
            ],
            'multipart' => [
                [
                    'name' => 'image',
                    'contents' => fopen($image->path(), 'r'),
                    'filename' => $image->getClientOriginalName()
                ],
                [
                    'name' => 'partner_id',
                    'contents' => $partnerId
                ],
                [
                    'name' => 'country',
                    'contents' => 'ZW'
                ],
                [
                    'name' => 'id_type',
                    'contents' => 'NATIONAL_ID'
                ]
            ]
        ]);

        return json_decode($response->getBody(), true);
    }
    */
}