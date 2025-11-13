<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferenceCodeService;
use App\Http\Requests\ReferenceCodeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReferenceCodeController extends Controller
{
    protected $referenceCodeService;

    /**
     * Create a new controller instance.
     *
     * @param ReferenceCodeService $referenceCodeService
     * @return void
     */
    public function __construct(ReferenceCodeService $referenceCodeService)
    {
        $this->referenceCodeService = $referenceCodeService;
    }

    /**
     * Validate a reference code or National ID
     *
     * @param ReferenceCodeRequest $request
     * @return JsonResponse
     */
    public function validate(ReferenceCodeRequest $request): JsonResponse
    {
        // The ReferenceCodeRequest handles validation and sanitization
        $code = $request->input('code');
        $isValid = $this->referenceCodeService->validateReferenceCode($code);

        return response()->json([
            'success' => $isValid,
            'message' => $isValid ? 'Reference code is valid' : 'Reference code is invalid or expired',
        ]);
    }

    /**
     * Get application state by reference code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getState(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reference code format',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = strtoupper($request->input('code'));
        $state = $this->referenceCodeService->getStateByReferenceCode($code);

        if (!$state) {
            return response()->json([
                'success' => false,
                'message' => 'Reference code is invalid or expired',
            ], 404);
        }

        // Get metadata for status information
        $metadata = $state->metadata ?? [];
        $status = $metadata['status'] ?? 'pending';
        
        // If the reference code is about to expire, extend it
        if ($state->reference_code_expires_at && $state->reference_code_expires_at->diffInDays(now()) < 5) {
            $this->referenceCodeService->extendReferenceCode($code);
        }

        // Return only necessary information for security reasons
        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $state->session_id,
                'current_step' => $state->current_step,
                'created_at' => $state->created_at,
                'updated_at' => $state->updated_at,
                'status' => $status,
                'reference_code' => $code,
                'reference_code_expires_at' => $state->reference_code_expires_at ? $state->reference_code_expires_at->format('Y-m-d H:i:s') : null,
            ],
        ]);
    }
    
    /**
     * Generate and store a reference code for an application
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'referenceCode' => 'nullable|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request format',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionId = $request->input('sessionId');
            
            // If a reference code is provided, use it; otherwise generate a new one
            if ($request->has('referenceCode')) {
                $referenceCode = strtoupper($request->input('referenceCode'));
                
                // Check if the provided code already exists
                if ($this->referenceCodeService->referenceCodeExists($referenceCode)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Reference code already exists',
                    ], 409);
                }
                
                // Store the provided reference code
                $this->referenceCodeService->storeReferenceCode($sessionId, $referenceCode);
            } else {
                // Generate and store a new reference code
                $referenceCode = $this->referenceCodeService->generateReferenceCode($sessionId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reference code generated successfully',
                'reference_code' => $referenceCode,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate reference code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}