<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ReferenceCodeController;
use App\Http\Controllers\Api\IDVerificationController;
use App\Http\Controllers\WhatsAppWebhookController;

// WhatsApp Webhook routes (supports both Twilio and Cloud API)
Route::prefix('whatsapp')->group(function () {
    // WhatsApp Cloud API webhook verification (GET request from Meta)
    Route::get('/webhook', [WhatsAppWebhookController::class, 'verifyWebhook'])->name('whatsapp.verify');
    // Incoming messages (POST from Twilio or Cloud API)
    Route::post('/webhook', [WhatsAppWebhookController::class, 'handleWebhook'])->name('whatsapp.webhook');
    // Status updates
    Route::post('/status', [WhatsAppWebhookController::class, 'handleStatusUpdate'])->name('whatsapp.status');
});

// Product API routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']); // Legacy endpoint
    Route::get('/frontend-catalog', [ProductController::class, 'getFrontendCatalog']); // Frontend-compatible format
    Route::get('/categories', [ProductController::class, 'getCategories']);
    Route::get('/category/{categoryId}', [ProductController::class, 'getProductsByCategory']);
    Route::get('/product/{productId}', [ProductController::class, 'getProduct']);
    Route::get('/search', [ProductController::class, 'searchProducts']);
    Route::get('/statistics', [ProductController::class, 'getStatistics']);
    Route::get('/supplier-info', [ProductController::class, 'getSupplierInfo']);
});

// Application State API routes
Route::prefix('states')->group(function () {
    Route::post('/save', [StateController::class, 'saveState']);
    Route::post('/retrieve', [StateController::class, 'retrieveState']);
    Route::post('/create-application', [StateController::class, 'createApplication']);
    Route::post('/link', [StateController::class, 'linkSessions']);
    Route::post('/check-existing', [StateController::class, 'checkExistingSession']);
    Route::post('/discard', [StateController::class, 'discardSession']);
});

// Agent API routes
Route::prefix('agents')->group(function () {
    Route::get('/', [AgentController::class, 'index']);
    Route::get('/types', [AgentController::class, 'getTypes']);
    Route::get('/code/{code}', [AgentController::class, 'getByCode']);
    Route::post('/validate-referral', [AgentController::class, 'validateReferral']);
});

// API Test endpoint
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working',
        'timestamp' => now()->toISOString()
    ]);
});

// ID Verification API route
Route::post('/verify-id-card', [IDVerificationController::class, 'verifyIDCard']);

// Document Upload API routes
Route::prefix('documents')->group(function () {
    Route::post('/upload', function (Request $request) {
        try {
            if (!$request->hasFile('file')) {
                return response()->json(['success' => false, 'message' => 'No file provided'], 400);
            }

            $file = $request->file('file');
            $documentType = $request->input('documentType', 'unknown');
            $sessionId = $request->input('sessionId');

            // SECURITY: Require a valid session ID
            if (!$sessionId || $sessionId === 'unknown') {
                return response()->json(['success' => false, 'message' => 'Valid session ID required'], 400);
            }

            // SECURITY: Sanitize sessionId to prevent directory traversal
            $sessionId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $sessionId);

            // Validate file
            if (!$file->isValid()) {
                return response()->json(['success' => false, 'message' => 'Invalid file'], 400);
            }

            // Get file info
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            // SECURITY: Validate file type - only allow safe document types
            $allowedMimeTypes = [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];

            if (!in_array($mimeType, $allowedMimeTypes)) {
                \Log::warning('Upload rejected - invalid mime type', [
                    'mime_type' => $mimeType,
                    'session_id' => $sessionId,
                    'ip' => $request->ip()
                ]);
                return response()->json(['success' => false, 'message' => 'File type not allowed'], 400);
            }

            if (!in_array($extension, $allowedExtensions)) {
                \Log::warning('Upload rejected - invalid extension', [
                    'extension' => $extension,
                    'session_id' => $sessionId,
                    'ip' => $request->ip()
                ]);
                return response()->json(['success' => false, 'message' => 'File extension not allowed'], 400);
            }

            // SECURITY: Validate file size (max 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($fileSize > $maxSize) {
                return response()->json(['success' => false, 'message' => 'File too large (max 10MB)'], 400);
            }

            // SECURITY: Sanitize document type
            $documentType = preg_replace('/[^a-zA-Z0-9\-_]/', '', $documentType);
            
            // Generate unique filename
            $filename = $documentType . '_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Store file using Laravel Storage (will use the configured disk)
            // This handles directory creation and permissions automatically
            $storagePath = 'documents/' . $sessionId;
            $path = $file->storeAs($storagePath, $filename, 'public');
            
            if (!$path) {
                return response()->json(['success' => false, 'message' => 'Failed to store uploaded file'], 500);
            }
            
            // Generate public URL
            $publicUrl = \Storage::disk('public')->url($path);
            
            return response()->json([
                'success' => true,
                'path' => $path, // Return relative path for backend storage usage
                'url' => $publicUrl, // Return public URL for frontend display
                'filename' => $filename,
                'originalName' => $originalName,
                'size' => $fileSize,
                'type' => $mimeType,
                'message' => 'File uploaded successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    });
    
    Route::post('/delete', function (Request $request) {
        try {
            $path = $request->input('path');
            $sessionId = $request->input('sessionId');

            if (!$path) {
                return response()->json(['success' => false, 'message' => 'No path provided'], 400);
            }

            if (!$sessionId) {
                return response()->json(['success' => false, 'message' => 'Session ID required'], 400);
            }

            // SECURITY: Sanitize path to prevent directory traversal attacks
            // Only allow alphanumeric, dashes, underscores, dots, and forward slashes
            $sanitizedPath = preg_replace('/[^a-zA-Z0-9\-_\.\/]/', '', $path);

            // SECURITY: Prevent path traversal - reject any path containing ".."
            if (strpos($sanitizedPath, '..') !== false) {
                \Log::warning('Path traversal attempt blocked', [
                    'original_path' => $path,
                    'session_id' => $sessionId,
                    'ip' => $request->ip()
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid path'], 400);
            }

            // SECURITY: Only allow deletion within the documents directory for this session
            $allowedPrefix = 'storage/documents/' . $sessionId . '/';
            $altAllowedPrefix = 'documents/' . $sessionId . '/';

            // Normalize path by removing leading slashes and "storage/" prefix if present
            $normalizedPath = ltrim($sanitizedPath, '/');
            if (strpos($normalizedPath, 'storage/') === 0) {
                $normalizedPath = substr($normalizedPath, 8); // Remove 'storage/'
            }

            // Check if path is within allowed directory
            if (strpos($normalizedPath, 'documents/' . $sessionId . '/') !== 0) {
                \Log::warning('Unauthorized file deletion attempt', [
                    'path' => $path,
                    'normalized' => $normalizedPath,
                    'session_id' => $sessionId,
                    'ip' => $request->ip()
                ]);
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            // Use Laravel Storage to delete the file safely
            if (\Storage::disk('public')->exists($normalizedPath)) {
                \Storage::disk('public')->delete($normalizedPath);
                \Log::info('File deleted successfully', [
                    'path' => $normalizedPath,
                    'session_id' => $sessionId
                ]);
                return response()->json(['success' => true, 'message' => 'File deleted successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'File not found'], 404);
            }

        } catch (\Exception $e) {
            \Log::error('File deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage()
            ], 500);
        }
    });
});

// Reference Code API routes
Route::prefix('reference-code')->group(function () {
    Route::post('/generate', [ReferenceCodeController::class, 'generate']);
    Route::post('/validate', [ReferenceCodeController::class, 'validate']);
    Route::post('/lookup', [ReferenceCodeController::class, 'getState']);
});

// Application Status API routes
Route::prefix('application')->group(function () {
    Route::get('/status/{reference}', [\App\Http\Controllers\ApplicationStatusController::class, 'getStatus']);
    Route::put('/status/{sessionId}', [\App\Http\Controllers\ApplicationStatusController::class, 'updateStatus'])->middleware('auth:sanctum');
    Route::post('/notifications/{reference}/mark-read', [\App\Http\Controllers\ApplicationStatusController::class, 'markNotificationsAsRead']);
    Route::get('/status-updates/{reference}', [\App\Http\Controllers\ApplicationStatusController::class, 'getStatusUpdates']);
    Route::get('/progress/{reference}', [\App\Http\Controllers\ApplicationStatusController::class, 'getProgressDetails']);
    Route::get('/insights/{reference}', [\App\Http\Controllers\ApplicationStatusController::class, 'getApplicationInsights']);
});

// User Pending Applications Check
Route::get('/user/pending-applications', [\App\Http\Controllers\PendingApplicationController::class, 'check']);

// SSB Loan Workflow API routes (Public - for clients)
Route::prefix('ssb')->group(function () {
    // Client-facing endpoints
    Route::post('/status/check', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'checkStatusByReference']);
    Route::post('/adjust-period', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'adjustLoanPeriod']);
    Route::post('/update-id', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'updateIDNumber']);
    Route::post('/decline-adjustment', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'declineAdjustment']);
});

// SSB Loan Workflow API routes (Admin - protected)
Route::prefix('admin/ssb')->middleware('auth:sanctum')->group(function () {
    // Admin endpoints
    Route::post('/{sessionId}/initialize', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'initializeSSBWorkflow']);
    Route::get('/{sessionId}/status', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'getSSBStatus']);
    Route::get('/{sessionId}/history', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'getSSBStatusHistory']);
    Route::post('/{sessionId}/manual-update', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'manualSSBStatusUpdate']);
    Route::post('/{sessionId}/simulate', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'simulateSSBResponse']);
    Route::post('/csv-upload', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'uploadSSBCSVResponse']);
    Route::get('/export-csv', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'exportSSBApplicationsCSV']);
});

// ZB Loan Workflow API routes (Public - for clients)
Route::prefix('zb')->group(function () {
    // Client-facing endpoints
    Route::post('/status/check', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'checkZBStatusByReference']);
    Route::post('/blacklist-report/decline', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'declineBlacklistReport']);
    Route::post('/blacklist-report/request', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'requestBlacklistReport']);
    Route::post('/blacklist-report/payment', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'processBlacklistReportPayment']);
    Route::post('/period-adjustment/decline', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'declineZBPeriodAdjustment']);
    Route::post('/period-adjustment/accept', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'acceptZBPeriodAdjustment']);
});

// ZB Loan Workflow API routes (Admin - protected)
Route::prefix('admin/zb')->middleware('auth:sanctum')->group(function () {
    // Admin endpoints
    Route::post('/{sessionId}/initialize', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'initializeZBWorkflow']);
    Route::get('/{sessionId}/status', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'getZBStatus']);
    Route::get('/{sessionId}/history', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'getZBStatusHistory']);

    // Admin manual status updates
    Route::post('/{sessionId}/credit-check/good', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'processCreditCheckGood']);
    Route::post('/{sessionId}/credit-check/poor', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'processCreditCheckPoor']);
    Route::post('/{sessionId}/salary-not-regular', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'processSalaryNotRegular']);
    Route::post('/{sessionId}/insufficient-salary', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'processInsufficientSalary']);
    Route::post('/{sessionId}/approved', [\App\Http\Controllers\Admin\ApplicationManagementController::class, 'processZBApproved']);
});

// Ecocash Payment API routes
Route::prefix('ecocash')->group(function () {
    // Public webhook endpoint for Ecocash callbacks
    Route::post('/webhook', [\App\Http\Controllers\EcocashWebhookController::class, 'handleWebhook'])->name('ecocash.webhook');

    // Client-facing endpoints
    Route::post('/initiate', [\App\Http\Controllers\EcocashWebhookController::class, 'initiatePayment']);
    Route::post('/check-status', [\App\Http\Controllers\EcocashWebhookController::class, 'checkStatus']);
});

// SECURITY: Ecocash simulate endpoint - only available in non-production environments
if (!app()->environment('production')) {
    Route::post('/ecocash/simulate', [\App\Http\Controllers\EcocashWebhookController::class, 'simulatePayment']);
}

// Delivery Tracking API routes
Route::prefix('delivery')->group(function () {
    Route::get('/tracking/{reference}', [\App\Http\Controllers\DeliveryTrackingController::class, 'getStatus']);
});

// Invoice SMS Notification API routes
Route::prefix('invoice-sms')->group(function () {
    Route::post('/hire-purchase', [\App\Http\Controllers\Api\InvoiceSMSController::class, 'sendHirePurchaseSMS']);
});

// SMS API routes
Route::post('/send-application-sms', function (Request $request) {
    try {
        $phoneNumber = $request->input('phoneNumber');
        $referenceCode = $request->input('referenceCode');
        $message = $request->input('message');

        if (!$phoneNumber || !$referenceCode || !$message) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required fields'
            ], 400);
        }

        // Dispatch background job for SMS sending
        try {
            \App\Jobs\SendSmsJob::dispatch($phoneNumber, $message, $referenceCode);

            \Log::info('Application SMS job dispatched', [
                'phone' => $phoneNumber,
                'reference' => $referenceCode
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS queued successfully',
                'data' => [
                    'phone' => $phoneNumber,
                    'status' => 'queued',
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('SMS job dispatch failed', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'reference_code' => $referenceCode
            ]);

            // Return success anyway for better UX (logged for debugging)
            // If the queue is down, we don't want to block the user
            return response()->json([
                'success' => true,
                'message' => 'Application completed successfully',
                'data' => [
                    'phone' => $phoneNumber,
                    'status' => 'queued_failed',
                    'timestamp' => now()->toISOString()
                ]
            ]);
        }
    } catch (\Exception $e) {
        \Log::error('Application SMS endpoint error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to process SMS request'
        ], 500);
    }
});

// Paynow Webhook route (for payment notifications)
Route::post('/paynow/webhook', function (Request $request) {
    try {
        $paynowService = app(\App\Services\PaynowService::class);
        $result = $paynowService->handleWebhook($request->all());

        if ($result['verified'] && $result['is_paid']) {
            // Log successful payment notification
            \Log::info('Paynow webhook: Payment confirmed', [
                'reference' => $result['reference'],
                'amount' => $result['amount'],
                'paynow_reference' => $result['paynow_reference'] ?? null,
            ]);
        }

        return response('OK', 200);
    } catch (\Exception $e) {
        \Log::error('Paynow webhook error: ' . $e->getMessage());
        return response('Error', 500);
    }
})->name('paynow.webhook');

// Paynow Routes
Route::post('/paynow/mobile-initiate', [App\Http\Controllers\PaynowController::class, 'initiateMobile']);
Route::post('/paynow/status', [App\Http\Controllers\PaynowController::class, 'checkStatus']);
Route::post('/loan-deposits/initiate', [App\Http\Controllers\LoanController::class, 'initiateDeposit']);




Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

