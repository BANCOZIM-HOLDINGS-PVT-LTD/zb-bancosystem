<?php

namespace App\Providers;

use App\Exceptions\PDF\PDFException;
use App\Http\Middleware\PDFErrorHandlingMiddleware;
use App\Services\PDFLoggingService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;

class PDFErrorHandlingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the PDF error handling middleware as a singleton
        $this->app->singleton(PDFErrorHandlingMiddleware::class, function ($app) {
            return new PDFErrorHandlingMiddleware($app->make(PDFLoggingService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the middleware for PDF-related routes
        $this->registerPDFRouteMiddleware();
        
        // Register global error handlers for PDF exceptions
        $this->registerPDFExceptionHandlers();
    }
    
    /**
     * Register the PDF route middleware
     */
    protected function registerPDFRouteMiddleware(): void
    {
        // Register the middleware with a name for use in route definitions
        Route::aliasMiddleware('pdf.error-handling', PDFErrorHandlingMiddleware::class);
        
        // Apply the middleware to PDF-related route groups
        Route::middlewareGroup('pdf', [
            'pdf.error-handling',
        ]);
    }
    
    /**
     * Register global exception handlers for PDF exceptions
     */
    protected function registerPDFExceptionHandlers(): void
    {
        // Get the exception handler
        $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->renderable(function (PDFException $e, $request) {
                $logger = $this->app->make(PDFLoggingService::class);
                
                // Log the exception
                $logger->logError('PDF exception caught by global handler', [
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                    'request_path' => $request->path(),
                    'request_method' => $request->method(),
                    'user_id' => $request->user() ? $request->user()->id : null,
                ], $e);
                
                // Return JSON response for API requests
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(
                        $e->toArray(),
                        $this->getPDFExceptionStatusCode($e)
                    );
                }
                
                // For web requests, if it's an AJAX request, return JSON
                if ($request->ajax()) {
                    return response()->json(
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
            'PDF_INCOMPLETE_DATA', 'VALIDATION_FAILED', 'APPLICATION_INCOMPLETE' => 400,
            'APPLICATION_NOT_FOUND' => 404,
            'PDF_STORAGE_FAILED', 'PDF_GENERATION_FAILED' => 500,
            default => 500,
        };
    }
}