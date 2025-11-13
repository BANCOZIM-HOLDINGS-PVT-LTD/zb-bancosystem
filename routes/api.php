<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ReferenceCodeController;
use App\Http\Controllers\Api\IDVerificationController;

// Product API routes
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']); // Legacy endpoint
    Route::get('/frontend-catalog', [ProductController::class, 'getFrontendCatalog']); // Frontend-compatible format
    Route::get('/categories', [ProductController::class, 'getCategories']);
    Route::get('/category/{categoryId}', [ProductController::class, 'getProductsByCategory']);
    Route::get('/product/{productId}', [ProductController::class, 'getProduct']);
    Route::get('/search', [ProductController::class, 'searchProducts']);
    Route::get('/statistics', [ProductController::class, 'getStatistics']);
});

// Application State API routes
Route::prefix('states')->group(function () {
    Route::post('/save', [StateController::class, 'saveState']);
    Route::post('/retrieve', [StateController::class, 'retrieveState']);
    Route::post('/create-application', [StateController::class, 'createApplication']);
    Route::post('/link', [StateController::class, 'linkSessions']);
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
            $sessionId = $request->input('sessionId', 'unknown');
            
            // Validate file
            if (!$file->isValid()) {
                return response()->json(['success' => false, 'message' => 'Invalid file'], 400);
            }
            
            // Get file info before moving
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $extension = $file->getClientOriginalExtension();
            
            // Create storage directory if it doesn't exist
            $uploadPath = public_path('storage/documents/' . $sessionId);
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Generate unique filename
            $filename = $documentType . '_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Move file to storage
            $destinationPath = $uploadPath . '/' . $filename;
            if (!$file->move($uploadPath, $filename)) {
                return response()->json(['success' => false, 'message' => 'Failed to move uploaded file'], 500);
            }
            
            // Verify file was moved successfully
            if (!file_exists($destinationPath)) {
                return response()->json(['success' => false, 'message' => 'File upload verification failed'], 500);
            }
            
            $filePath = 'storage/documents/' . $sessionId . '/' . $filename;
            
            return response()->json([
                'success' => true,
                'path' => $filePath,
                'filename' => $filename,
                'originalName' => $originalName,
                'size' => $fileSize,
                'type' => $mimeType,
                'message' => 'File uploaded successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    });
    
    Route::post('/delete', function (Request $request) {
        try {
            $path = $request->input('path');
            if (!$path) {
                return response()->json(['success' => false, 'message' => 'No path provided'], 400);
            }
            
            $fullPath = public_path($path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
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

// Delivery Tracking API routes
Route::prefix('delivery')->group(function () {
    Route::get('/tracking/{reference}', [\App\Http\Controllers\DeliveryTrackingController::class, 'getStatus']);
});

// Cash Purchase API routes
Route::prefix('cash-purchases')->group(function () {
    Route::post('/', [\App\Http\Controllers\CashPurchaseController::class, 'store']);
    Route::get('/{purchaseNumber}', [\App\Http\Controllers\CashPurchaseController::class, 'show']);
    Route::post('/track', [\App\Http\Controllers\CashPurchaseController::class, 'track']);
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

        // Format phone number (ensure it's in the correct format for Zimbabwe)
        $formattedPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (substr($formattedPhone, 0, 1) === '0') {
            $formattedPhone = '263' . substr($formattedPhone, 1);
        } elseif (substr($formattedPhone, 0, 3) !== '263') {
            $formattedPhone = '263' . $formattedPhone;
        }

        // Log the SMS request
        \Log::info('Application SMS request', [
            'phone' => $formattedPhone,
            'reference' => $referenceCode,
            'message_preview' => substr($message, 0, 50) . '...'
        ]);

        // Check if SMS service exists, otherwise simulate success
        try {
            if (class_exists('\App\Services\SmsService')) {
                $smsService = app(\App\Services\SmsService::class);
                $result = $smsService->send($formattedPhone, $message);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $result
                ]);
            } else {
                // Simulate successful SMS for development
                \Log::info('SMS would be sent (no service configured)', [
                    'to' => $formattedPhone,
                    'message' => $message
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully (simulated)',
                    'data' => [
                        'phone' => $formattedPhone,
                        'status' => 'sent',
                        'timestamp' => now()->toISOString()
                    ]
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('SMS sending failed', [
                'error' => $e->getMessage(),
                'phone' => $formattedPhone
            ]);

            // Return success anyway for better UX (logged for debugging)
            return response()->json([
                'success' => true,
                'message' => 'Application completed successfully',
                'data' => [
                    'phone' => $formattedPhone,
                    'status' => 'queued',
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
            // Update the cash purchase payment status
            $purchase = \App\Models\CashPurchase::where('purchase_number', $result['reference'])->first();

            if ($purchase) {
                $purchase->markAsPaid($result['paynow_reference']);
                \Log::info('Paynow webhook: Payment confirmed', [
                    'purchase_number' => $result['reference'],
                    'amount' => $result['amount'],
                ]);
            }
        }

        return response('OK', 200);
    } catch (\Exception $e) {
        \Log::error('Paynow webhook error: ' . $e->getMessage());
        return response('Error', 500);
    }
})->name('paynow.webhook');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
