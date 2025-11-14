<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentUploadRequest;
use App\Services\DocumentValidationService;
use App\Services\PDFLoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentUploadController extends Controller
{
    protected $documentValidationService;

    protected $pdfLoggingService;

    /**
     * Constructor
     */
    public function __construct(
        DocumentValidationService $documentValidationService,
        PDFLoggingService $pdfLoggingService
    ) {
        $this->documentValidationService = $documentValidationService;
        $this->pdfLoggingService = $pdfLoggingService;
    }

    /**
     * Upload a document
     */
    public function upload(DocumentUploadRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $file = $request->file('file');
            $documentType = $validated['document_type'];
            $sessionId = $validated['session_id'];

            // Validate document
            $validationResult = $this->documentValidationService->validateDocument($file, [
                'maxSize' => 10 * 1024 * 1024, // 10MB
                'minSize' => 1024, // 1KB
                'allowedTypes' => ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
                'maxNameLength' => 100,
            ]);

            // If validation fails, return errors
            if (! $validationResult['isValid']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validationResult['errors'],
                    'metadata' => $validationResult['metadata'],
                ], 422);
            }

            // Store document
            $storeResult = $this->documentValidationService->storeDocument($file, $documentType, $sessionId);

            // Log successful upload
            $this->pdfLoggingService->logInfo('Document uploaded successfully', [
                'documentType' => $documentType,
                'sessionId' => $sessionId,
                'filename' => $file->getClientOriginalName(),
                'path' => $storeResult['path'],
                'metadata' => $storeResult['metadata'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'path' => $storeResult['path'],
                'url' => Storage::disk('public')->url($storeResult['path']),
                'metadata' => $storeResult['metadata'],
            ]);
        } catch (\Exception $e) {
            // Log error
            $this->pdfLoggingService->logError('Error uploading document', [
                'documentType' => $request->input('documentType'),
                'sessionId' => $request->input('sessionId'),
                'filename' => $request->file('file')->getClientOriginalName(),
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error uploading document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate a document without uploading
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validate(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');

            // Validate document
            $validationResult = $this->documentValidationService->validateDocument($file);

            return response()->json([
                'success' => true,
                'isValid' => $validationResult['isValid'],
                'errors' => $validationResult['errors'],
                'metadata' => $validationResult['metadata'],
            ]);
        } catch (\Exception $e) {
            // Log error
            $this->pdfLoggingService->logError('Error validating document', [
                'filename' => $request->file('file')->getClientOriginalName(),
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error validating document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get document metadata
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMetadata(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $path = $request->input('path');

            // Check if file exists
            if (! Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            // Get file metadata
            $file = Storage::disk('public')->path($path);
            $metadata = [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'size' => Storage::disk('public')->size($path),
                'lastModified' => Storage::disk('public')->lastModified($path),
                'mimeType' => Storage::disk('public')->mimeType($path),
            ];

            // Get additional metadata based on file type
            $mimeType = Storage::disk('public')->mimeType($path);
            if (strpos($mimeType, 'image/') === 0) {
                // Get image dimensions
                [$width, $height] = getimagesize($file);
                $metadata['dimensions'] = [
                    'width' => $width,
                    'height' => $height,
                ];
            } elseif ($mimeType === 'application/pdf') {
                // Get PDF page count
                $content = file_get_contents($file);
                preg_match_all('/\/Type\s*\/Page[^s]/i', $content, $matches);
                $metadata['pageCount'] = count($matches[0]);
            }

            return response()->json([
                'success' => true,
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            // Log error
            $this->pdfLoggingService->logError('Error getting document metadata', [
                'path' => $request->input('path'),
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error getting document metadata',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a document
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $path = $request->input('path');

            // Check if file exists
            if (! Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            // Delete file
            Storage::disk('public')->delete($path);

            // Log deletion
            $this->pdfLoggingService->logInfo('Document deleted', [
                'path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ]);
        } catch (\Exception $e) {
            // Log error
            $this->pdfLoggingService->logError('Error deleting document', [
                'path' => $request->input('path'),
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch upload documents
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchUpload(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'documentType' => 'required|string|max:50',
            'sessionId' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $files = $request->file('files');
            $documentType = $request->input('documentType');
            $sessionId = $request->input('sessionId');

            $results = [];
            $hasErrors = false;

            foreach ($files as $file) {
                // Validate document
                $validationResult = $this->documentValidationService->validateDocument($file);

                if (! $validationResult['isValid']) {
                    $results[] = [
                        'filename' => $file->getClientOriginalName(),
                        'success' => false,
                        'errors' => $validationResult['errors'],
                        'metadata' => $validationResult['metadata'],
                    ];
                    $hasErrors = true;

                    continue;
                }

                // Store document
                $storeResult = $this->documentValidationService->storeDocument($file, $documentType, $sessionId);

                // Log successful upload
                $this->pdfLoggingService->logInfo('Document uploaded successfully (batch)', [
                    'documentType' => $documentType,
                    'sessionId' => $sessionId,
                    'filename' => $file->getClientOriginalName(),
                    'path' => $storeResult['path'],
                ]);

                $results[] = [
                    'filename' => $file->getClientOriginalName(),
                    'success' => true,
                    'path' => $storeResult['path'],
                    'url' => Storage::disk('public')->url($storeResult['path']),
                    'metadata' => $storeResult['metadata'],
                ];
            }

            return response()->json([
                'success' => ! $hasErrors,
                'message' => $hasErrors ? 'Some files failed to upload' : 'All files uploaded successfully',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            // Log error
            $this->pdfLoggingService->logError('Error in batch document upload', [
                'documentType' => $request->input('documentType'),
                'sessionId' => $request->input('sessionId'),
                'fileCount' => count($request->file('files')),
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error uploading documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify document integrity
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyIntegrity(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'hash' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $path = $request->input('path');
            $providedHash = $request->input('hash');

            // Check if file exists
            if (! Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                    'verified' => false,
                ], 404);
            }

            // Calculate hash of the stored file
            $filePath = Storage::disk('public')->path($path);
            $calculatedHash = hash_file('sha256', $filePath);

            // Compare hashes
            $verified = hash_equals($calculatedHash, $providedHash);

            // Log verification attempt
            $this->pdfLoggingService->logInfo('Document integrity verification', [
                'path' => $path,
                'verified' => $verified,
            ]);

            return response()->json([
                'success' => true,
                'verified' => $verified,
                'message' => $verified ? 'Document integrity verified' : 'Document integrity verification failed',
            ]);
        } catch (\Exception $e) {
            // Log error
            $this->pdfLoggingService->logError('Error verifying document integrity', [
                'path' => $request->input('path'),
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'Error verifying document integrity',
                'error' => $e->getMessage(),
                'verified' => false,
            ], 500);
        }
    }
}
