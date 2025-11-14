<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientErrorController extends Controller
{
    /**
     * Log client-side errors
     */
    public function logError(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'error' => 'required|array',
            'error.message' => 'required|string|max:1000',
            'error.stack' => 'nullable|string|max:5000',
            'error.name' => 'nullable|string|max:100',
            'errorInfo' => 'nullable|array',
            'errorInfo.componentStack' => 'nullable|string|max:5000',
            'context' => 'nullable|string|max:500',
            'url' => 'required|url|max:500',
            'userAgent' => 'nullable|string|max:500',
            'timestamp' => 'required|date',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid error data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Sanitize and prepare error data for logging
        $errorData = [
            'type' => 'client_error',
            'message' => $validated['error']['message'],
            'stack' => $validated['error']['stack'] ?? null,
            'error_name' => $validated['error']['name'] ?? 'Unknown',
            'component_stack' => $validated['errorInfo']['componentStack'] ?? null,
            'context' => $validated['context'] ?? null,
            'url' => $validated['url'],
            'user_agent' => $validated['userAgent'] ?? $request->userAgent(),
            'ip_address' => $request->ip(),
            'timestamp' => $validated['timestamp'],
            'session_id' => $request->session()->getId(),
            'user_id' => $request->user()?->id,
            'metadata' => $validated['metadata'] ?? [],
        ];

        // Determine log level based on error type
        $logLevel = $this->determineLogLevel($validated['error']['message'], $validated['error']['name'] ?? '');

        // Log the error
        Log::channel('stack')->log($logLevel, 'Client-side error occurred', $errorData);

        // In production, you might want to send critical errors to external monitoring
        if (app()->environment('production') && $logLevel === 'error') {
            $this->sendToExternalMonitoring($errorData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Error logged successfully',
        ]);
    }

    /**
     * Determine the appropriate log level based on error details
     */
    private function determineLogLevel(string $message, string $errorName): string
    {
        // Critical errors that should be treated as errors
        $criticalPatterns = [
            'ChunkLoadError',
            'TypeError',
            'ReferenceError',
            'SyntaxError',
            'Network Error',
            'Failed to fetch',
        ];

        foreach ($criticalPatterns as $pattern) {
            if (stripos($message, $pattern) !== false || stripos($errorName, $pattern) !== false) {
                return 'error';
            }
        }

        // Warning level for less critical issues
        $warningPatterns = [
            'Warning',
            'Deprecated',
            'ResizeObserver',
        ];

        foreach ($warningPatterns as $pattern) {
            if (stripos($message, $pattern) !== false || stripos($errorName, $pattern) !== false) {
                return 'warning';
            }
        }

        // Default to info level
        return 'info';
    }

    /**
     * Send critical errors to external monitoring service
     */
    private function sendToExternalMonitoring(array $errorData): void
    {
        try {
            // Here you would integrate with services like:
            // - Sentry
            // - Bugsnag
            // - Rollbar
            // - Custom monitoring endpoint

            // Example for a custom monitoring endpoint:
            // Http::post(config('monitoring.external_endpoint'), $errorData);

            Log::info('Error sent to external monitoring', [
                'error_message' => $errorData['message'],
                'url' => $errorData['url'],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send error to external monitoring', [
                'error' => $e->getMessage(),
                'original_error' => $errorData['message'],
            ]);
        }
    }
}
