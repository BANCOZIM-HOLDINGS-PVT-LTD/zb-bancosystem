<?php

namespace App\Exceptions;

use App\Exceptions\PDF\PDFException;
use App\Services\PDFLoggingService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        
        // Handle PDF exceptions
        $this->renderable(function (PDFException $e, Request $request) {
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log the exception
            $pdfLogger->logError('PDF exception caught by global handler', [
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext(),
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'user_id' => $request->user() ? $request->user()->id : null,
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
                    'pdf_error_details' => json_encode($e->getContext())
                ]);
        });
    }
    
    /**
     * Get appropriate HTTP status code for PDF exceptions
     *
     * @param PDFException $e The PDF exception
     * @return int HTTP status code
     */
    private function getPDFExceptionStatusCode(PDFException $e): int
    {
        $errorCode = $e->getErrorCode();
        
        return match($errorCode) {
            'PDF_INCOMPLETE_DATA', 'VALIDATION_FAILED' => Response::HTTP_BAD_REQUEST,
            'APPLICATION_NOT_FOUND' => Response::HTTP_NOT_FOUND,
            'PDF_STORAGE_FAILED', 'PDF_GENERATION_FAILED' => Response::HTTP_INTERNAL_SERVER_ERROR,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}