<?php

namespace App\Http\Middleware;

use App\Exceptions\PDF\PDFException;
use App\Services\PDFLoggingService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for handling PDF-related errors consistently across the application
 */
class PDFErrorHandlingMiddleware
{
    /**
     * The PDF logging service instance
     */
    protected PDFLoggingService $logger;

    /**
     * Create a new middleware instance
     */
    public function __construct(PDFLoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle an incoming request
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Process the request
            $response = $next($request);

            return $response;
        } catch (PDFException $e) {
            // Log the exception with appropriate level based on error type
            $this->logger->logError('PDF operation failed', [
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext(),
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ], $e);

            // Return JSON response for API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return new JsonResponse(
                    $e->toArray(),
                    $this->getPDFExceptionStatusCode($e)
                );
            }

            // For web requests, if it's an AJAX request, return JSON
            if ($request->ajax()) {
                return new JsonResponse(
                    $e->toArray(),
                    $this->getPDFExceptionStatusCode($e)
                );
            }

            // For regular web requests, redirect with error message
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'pdf_error' => $e->getMessage(),
                    'pdf_error_code' => $e->getErrorCode(),
                    'pdf_error_details' => json_encode($e->getContext()),
                ]);
        } catch (\Exception $e) {
            // Log unexpected error
            $this->logger->logError('Unexpected error during PDF operation', [
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'critical' => true,
            ], $e);

            // Return JSON response for API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return new JsonResponse([
                    'error' => 'An unexpected error occurred',
                    'message' => $e->getMessage(),
                    'code' => 'UNEXPECTED_ERROR',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // For web requests, if it's an AJAX request, return JSON
            if ($request->ajax()) {
                return new JsonResponse([
                    'error' => 'An unexpected error occurred',
                    'message' => $e->getMessage(),
                    'code' => 'UNEXPECTED_ERROR',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // For regular web requests, redirect with error message
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'pdf_error' => 'An unexpected error occurred: '.$e->getMessage(),
                    'pdf_error_code' => 'UNEXPECTED_ERROR',
                ]);
        }
    }

    /**
     * Get appropriate HTTP status code for PDF exceptions
     *
     * @param  PDFException  $e  The PDF exception
     * @return int HTTP status code
     */
    private function getPDFExceptionStatusCode(PDFException $e): int
    {
        $errorCode = $e->getErrorCode();

        return match ($errorCode) {
            'PDF_INCOMPLETE_DATA', 'VALIDATION_FAILED', 'APPLICATION_INCOMPLETE' => Response::HTTP_BAD_REQUEST,
            'APPLICATION_NOT_FOUND' => Response::HTTP_NOT_FOUND,
            'PDF_STORAGE_FAILED', 'PDF_GENERATION_FAILED' => Response::HTTP_INTERNAL_SERVER_ERROR,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}
