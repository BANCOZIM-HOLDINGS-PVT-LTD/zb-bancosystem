<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use App\Services\PDFGeneratorService;
use App\Services\PDFLoggingService;
use App\Services\PDFBatchProcessingService;
use App\Exceptions\PDF\PDFException;
use App\Exceptions\PDF\PDFGenerationException;
use App\Exceptions\PDF\PDFStorageException;
use App\Exceptions\PDF\PDFIncompleteDataException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApplicationPDFController extends Controller
{
    private PDFGeneratorService $pdfGenerator;
    private PDFLoggingService $logger;
    private PDFBatchProcessingService $batchProcessor;
    
    /**
     * Cache TTL for PDF paths (in seconds)
     * 30 minutes default cache time
     */
    private const PDF_CACHE_TTL = 1800;
    
    public function __construct(
        PDFGeneratorService $pdfGenerator,
        PDFLoggingService $logger,
        PDFBatchProcessingService $batchProcessor
    ) {
        $this->pdfGenerator = $pdfGenerator;
        $this->logger = $logger;
        $this->batchProcessor = $batchProcessor;
    }
    
    /**
     * Download application PDF
     * 
     * @param Request $request The HTTP request
     * @param string $sessionId The application session ID
     * @return Response|StreamedResponse The HTTP response
     * 
     * @throws NotFoundHttpException When application is not found
     * @throws BadRequestHttpException When application is incomplete
     */
    public function download(Request $request, string $sessionId)
    {
        try {
            // Find the application state
            $state = $this->getApplicationState($sessionId);
            
            // Get PDF path with caching
            $pdfPath = $this->getPdfPathWithCache($state);
            
            // Get filename from path
            $filename = basename($pdfPath);
            
            // Set cache control headers for better performance
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'private, max-age=600', // 10 minutes client-side cache
                'Pragma' => 'private',
                'Expires' => gmdate('D, d M Y H:i:s', time() + 600) . ' GMT',
            ];
            
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log successful download
            $pdfLogger->logInfo('PDF downloaded successfully', [
                'session_id' => $sessionId,
                'pdf_path' => $pdfPath,
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'file_size' => Storage::disk('public')->size($pdfPath),
            ]);
            
            // Download the PDF with appropriate headers
            return Storage::disk('public')->download($pdfPath, $filename, $headers);
        } catch (PDFException $e) {
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log the error with appropriate level based on error type
            if ($e instanceof PDFIncompleteDataException) {
                $pdfLogger->logInfo('PDF download failed: Incomplete data', [
                    'session_id' => $sessionId,
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ], $e);
            } else {
                $pdfLogger->logError('PDF download failed', [
                    'session_id' => $sessionId,
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ], $e);
            }
            
            // Return JSON response with error details
            return response()->json($e->toArray(), $this->getPDFExceptionStatusCode($e));
        } catch (\Exception $e) {
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log unexpected error
            $pdfLogger->logError('PDF download failed: Unexpected error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'critical' => true,
            ], $e);
            
            // Wrap in PDFGenerationException for consistent error handling
            $pdfException = new PDFGenerationException(
                "Failed to generate or download PDF: Unexpected error",
                [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ],
                0,
                $e
            );
            
            return response()->json($pdfException->toArray(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * View application PDF in browser
     * 
     * @param Request $request The HTTP request
     * @param string $sessionId The application session ID
     * @return Response|StreamedResponse The HTTP response
     * 
     * @throws NotFoundHttpException When application is not found
     * @throws BadRequestHttpException When application is incomplete
     */
    public function view(Request $request, string $sessionId)
    {
        try {
            // Find the application state
            $state = $this->getApplicationState($sessionId);
            
            // Get PDF path with caching
            $pdfPath = $this->getPdfPathWithCache($state);
            
            // Set cache control headers for better performance
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($pdfPath) . '"',
                'Cache-Control' => 'private, max-age=600', // 10 minutes client-side cache
                'Pragma' => 'private',
                'Expires' => gmdate('D, d M Y H:i:s', time() + 600) . ' GMT',
            ];
            
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log successful view
            $pdfLogger->logInfo('PDF viewed successfully', [
                'session_id' => $sessionId,
                'pdf_path' => $pdfPath,
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'file_size' => Storage::disk('public')->size($pdfPath),
            ]);
            
            // Display the PDF inline with appropriate headers
            return response()->file(Storage::disk('public')->path($pdfPath), $headers);
        } catch (PDFException $e) {
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log the error with appropriate level based on error type
            if ($e instanceof PDFIncompleteDataException) {
                $pdfLogger->logInfo('PDF view failed: Incomplete data', [
                    'session_id' => $sessionId,
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ], $e);
            } else {
                $pdfLogger->logError('PDF view failed', [
                    'session_id' => $sessionId,
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ], $e);
            }
            
            // Return JSON response with error details
            return response()->json($e->toArray(), $this->getPDFExceptionStatusCode($e));
        } catch (\Exception $e) {
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log unexpected error
            $pdfLogger->logError('PDF view failed: Unexpected error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'critical' => true,
            ], $e);
            
            // Wrap in PDFGenerationException for consistent error handling
            $pdfException = new PDFGenerationException(
                "Failed to generate or view PDF: Unexpected error",
                [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ],
                0,
                $e
            );
            
            return response()->json($pdfException->toArray(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get application state by session ID
     * 
     * @param string $sessionId The application session ID
     * @return ApplicationState The application state
     * 
     * @throws PDFException When application is not found or incomplete
     */
    private function getApplicationState(string $sessionId): ApplicationState
    {
        // Find the application state
        $state = ApplicationState::where('session_id', $sessionId)->first();
        
        if (!$state) {
            throw new PDFException(
                "Application not found",
                "APPLICATION_NOT_FOUND",
                [
                    'session_id' => $sessionId,
                    'message' => "Application with session ID '{$sessionId}' not found"
                ],
                404
            );
        }
        
        // Allow PDF generation for applications that have enough data
        $allowedSteps = ['completed', 'in_review', 'summary', 'documents'];
        if (!in_array($state->current_step, $allowedSteps)) {
            throw new PDFIncompleteDataException(
                "Application incomplete",
                [
                    'session_id' => $sessionId,
                    'current_step' => $state->current_step,
                    'message' => "Application with session ID '{$sessionId}' is not ready for PDF generation. Current step: {$state->current_step}"
                ],
                400
            );
        }
        
        return $state;
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
    
    /**
     * Get PDF path with caching
     * 
     * @param ApplicationState $state The application state
     * @return string The PDF file path
     * 
     * @throws PDFException When PDF generation or storage fails
     */
    private function getPdfPathWithCache(ApplicationState $state): string
    {
        $sessionId = $state->session_id;
        $cacheKey = "pdf_path_{$sessionId}";
        
        // Try to get PDF path from cache
        try {
            $pdfPath = Cache::remember($cacheKey, self::PDF_CACHE_TTL, function () use ($state, $sessionId) {
                $formData = $state->form_data ?? [];
                
                // Check if PDF already generated and exists
                if (isset($formData['pdfPath']) && Storage::disk('public')->exists($formData['pdfPath'])) {
                    Log::info('Using cached PDF path', [
                        'session_id' => $sessionId,
                        'pdf_path' => $formData['pdfPath']
                    ]);
                    return $formData['pdfPath'];
                }
                
                // Generate new PDF - this will throw our custom exceptions if it fails
                $pdfPath = $this->pdfGenerator->generateApplicationPDF($state);
                
                // Save PDF path in application state
                $formData['pdfPath'] = $pdfPath;
                $formData['pdfGeneratedAt'] = now()->toISOString();
                $state->update(['form_data' => $formData]);
                
                Log::info('Generated new PDF', [
                    'session_id' => $sessionId,
                    'pdf_path' => $pdfPath
                ]);
                
                return $pdfPath;
            });
            
            // Verify PDF still exists (it might have been deleted after caching)
            if (!Storage::disk('public')->exists($pdfPath)) {
                Log::warning('Cached PDF not found, regenerating', [
                    'session_id' => $sessionId,
                    'pdf_path' => $pdfPath
                ]);
                
                // Clear cache
                Cache::forget($cacheKey);
                
                // Regenerate PDF - this will throw our custom exceptions if it fails
                $formData = $state->form_data ?? [];
                $pdfPath = $this->pdfGenerator->generateApplicationPDF($state);
                
                // Save PDF path
                $formData['pdfPath'] = $pdfPath;
                $formData['pdfGeneratedAt'] = now()->toISOString();
                $state->update(['form_data' => $formData]);
                
                Log::info('Regenerated missing PDF', [
                    'session_id' => $sessionId,
                    'pdf_path' => $pdfPath
                ]);
            }
            
            return $pdfPath;
        } catch (PDFException $e) {
            // Re-throw PDFExceptions
            throw $e;
        } catch (\Exception $e) {
            // Wrap any other exceptions in PDFGenerationException
            Log::error('Unexpected error during PDF path retrieval', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new PDFGenerationException(
                "Failed to retrieve or generate PDF: {$e->getMessage()}",
                [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ],
                0,
                $e
            );
        }
    }
    
    /**
     * Regenerate PDF (for admin use)
     * 
     * @param Request $request The HTTP request
     * @param string $sessionId The application session ID
     * @return Response The HTTP response
     */
    public function regenerate(Request $request, string $sessionId)
    {
        try {
            // Validate request
            $request->validate([
                'force' => 'boolean',
                'reason' => 'nullable|string|max:255',
            ]);
            
            // Find the application state
            $state = $this->getApplicationState($sessionId);
            
            // Get form data
            $formData = $state->form_data ?? [];
            $oldPdfPath = $formData['pdfPath'] ?? null;
            $force = $request->input('force', false);
            $reason = $request->input('reason', 'Manual regeneration');
            
            // Check if regeneration is necessary
            if (!$force && isset($oldPdfPath) && Storage::disk('public')->exists($oldPdfPath)) {
                // Check if PDF was generated recently (within last hour)
                $lastGenerated = isset($formData['pdfGeneratedAt']) 
                    ? new \DateTime($formData['pdfGeneratedAt']) 
                    : null;
                
                if ($lastGenerated && $lastGenerated > new \DateTime('-1 hour')) {
                    return response()->json([
                        'warning' => 'PDF was generated recently',
                        'message' => 'The PDF was generated less than an hour ago. Use force=true to regenerate anyway.',
                        'path' => $oldPdfPath,
                        'generated_at' => $formData['pdfGeneratedAt'],
                        'download_url' => route('application.pdf.download', $sessionId),
                        'view_url' => route('application.pdf.view', $sessionId)
                    ], Response::HTTP_OK);
                }
            }
            
            // Clear PDF cache
            Cache::forget("pdf_path_{$sessionId}");
            
            try {
                // Create backup of old PDF if it exists
                if (isset($oldPdfPath) && Storage::disk('public')->exists($oldPdfPath)) {
                    $backupPath = 'applications/backups/' . pathinfo($oldPdfPath, PATHINFO_FILENAME) . '_' . time() . '.pdf';
                    
                    // Ensure backup directory exists
                    if (!Storage::disk('public')->exists('applications/backups')) {
                        Storage::disk('public')->makeDirectory('applications/backups');
                    }
                    
                    // Copy old PDF to backup location
                    Storage::disk('public')->copy($oldPdfPath, $backupPath);
                    
                    // Delete old PDF
                    Storage::disk('public')->delete($oldPdfPath);
                    
                    // Store backup information
                    if (!isset($formData['pdfHistory'])) {
                        $formData['pdfHistory'] = [];
                    }
                    
                    $formData['pdfHistory'][] = [
                        'originalPath' => $oldPdfPath,
                        'backupPath' => $backupPath,
                        'regeneratedAt' => now()->toISOString(),
                        'reason' => $reason,
                        'regeneratedBy' => $request->user() ? $request->user()->id : 'system',
                    ];
                    
                    Log::info('PDF backup created', [
                        'session_id' => $sessionId,
                        'original_path' => $oldPdfPath,
                        'backup_path' => $backupPath
                    ]);
                }
            } catch (\Exception $e) {
                // Log backup failure but continue with regeneration
                Log::warning('PDF backup failed during regeneration', [
                    'session_id' => $sessionId,
                    'old_path' => $oldPdfPath,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Generate new PDF - this will throw our custom exceptions if it fails
            $pdfPath = $this->pdfGenerator->generateApplicationPDF($state);
            
            // Save new PDF path and metadata
            $formData['pdfPath'] = $pdfPath;
            $formData['pdfGeneratedAt'] = now()->toISOString();
            $formData['pdfRegenerationReason'] = $reason;
            $formData['pdfRegeneratedBy'] = $request->user() ? $request->user()->id : 'system';
            
            // Update application state
            $state->update(['form_data' => $formData]);
            
            // Log regeneration event
            Log::info('PDF regenerated', [
                'session_id' => $sessionId,
                'old_path' => $oldPdfPath,
                'new_path' => $pdfPath,
                'reason' => $reason,
                'user_id' => $request->user() ? $request->user()->id : 'system',
                'user_ip' => $request->ip(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF regenerated successfully',
                'path' => $pdfPath,
                'generated_at' => $formData['pdfGeneratedAt'],
                'download_url' => route('application.pdf.download', $sessionId),
                'view_url' => route('application.pdf.view', $sessionId)
            ], Response::HTTP_OK);
        } catch (PDFException $e) {
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log the error with appropriate level based on error type
            if ($e instanceof PDFIncompleteDataException) {
                $pdfLogger->logInfo('PDF regeneration failed: Incomplete data', [
                    'session_id' => $sessionId,
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'reason' => $request->input('reason', 'Manual regeneration'),
                ], $e);
            } else {
                $pdfLogger->logError('PDF regeneration failed', [
                    'session_id' => $sessionId,
                    'error_code' => $e->getErrorCode(),
                    'context' => $e->getContext(),
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'reason' => $request->input('reason', 'Manual regeneration'),
                ], $e);
            }
            
            // Return JSON response with error details
            return response()->json($e->toArray(), $this->getPDFExceptionStatusCode($e));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log validation error
            $pdfLogger->logInfo('PDF regeneration failed: Validation error', [
                'session_id' => $sessionId,
                'errors' => $e->errors(),
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'The provided data is invalid',
                'code' => 'VALIDATION_FAILED',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log unexpected error
            $pdfLogger->logError('PDF regeneration failed: Unexpected error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'critical' => true,
            ], $e);
            
            // Wrap in PDFGenerationException for consistent error handling
            $pdfException = new PDFGenerationException(
                "Failed to regenerate PDF: Unexpected error",
                [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ],
                0,
                $e
            );
            
            return response()->json($pdfException->toArray(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Batch download multiple PDFs
     * 
     * @param Request $request The HTTP request
     * @return Response|StreamedResponse The HTTP response
     */
    public function batchDownload(Request $request)
    {
        try {
            // Validate request with more specific rules
            $request->validate([
                'session_ids' => 'required|array|min:1|max:100', // Limit to 100 PDFs at once
                'session_ids.*' => 'required|string|distinct',
                'include_metadata' => 'boolean',
                'batch_name' => 'nullable|string|max:100',
            ]);
            
            $sessionIds = $request->input('session_ids');
            $includeMetadata = $request->input('include_metadata', false);
            $batchName = $request->input('batch_name', 'applications');
            
            // Create a unique batch ID for tracking
            $batchId = uniqid('batch_');
            
            // Get PDF logging service
            $pdfLogger = app(PDFLoggingService::class);
            
            // Log batch download start
            $pdfLogger->logInfo('Batch PDF download started', [
                'batch_id' => $batchId,
                'session_count' => count($sessionIds),
                'user_id' => $request->user() ? $request->user()->id : 'system',
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Start tracking progress
            $progressKey = "pdf_batch_{$batchId}_progress";
            Cache::put($progressKey, [
                'total' => count($sessionIds),
                'processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'status' => 'processing',
                'started_at' => now()->toISOString(),
            ], 3600); // Cache for 1 hour
            
            // Process PDFs in chunks for large batches
            $chunkSize = 10; // Process 10 PDFs at a time
            $results = [];
            $successCount = 0;
            $failureCount = 0;
            
            // Process in chunks to avoid memory issues
            foreach (array_chunk($sessionIds, $chunkSize) as $chunkIndex => $chunk) {
                $chunkResults = $this->processSessionIdChunk($chunk, $batchId, $chunkIndex);
                $results = array_merge($results, $chunkResults);
                
                // Update progress
                $processed = min(($chunkIndex + 1) * $chunkSize, count($sessionIds));
                $successCount += count(array_filter($chunkResults, fn($r) => $r['status'] === 'success'));
                $failureCount += count(array_filter($chunkResults, fn($r) => $r['status'] === 'error'));
                
                Cache::put($progressKey, [
                    'total' => count($sessionIds),
                    'processed' => $processed,
                    'successful' => $successCount,
                    'failed' => $failureCount,
                    'status' => 'processing',
                    'started_at' => Cache::get($progressKey)['started_at'],
                ], 3600);
            }
            
            // Update final progress
            Cache::put($progressKey, [
                'total' => count($sessionIds),
                'processed' => count($sessionIds),
                'successful' => $successCount,
                'failed' => $failureCount,
                'status' => 'completed',
                'started_at' => Cache::get($progressKey)['started_at'],
                'completed_at' => now()->toISOString(),
            ], 3600);
            
            // Check if we have any successful results
            $successfulResults = array_filter($results, fn($r) => $r['status'] === 'success');
            
            if (empty($successfulResults)) {
                // No successful PDFs
                Log::warning('Batch PDF download failed: No PDFs generated', [
                    'batch_id' => $batchId,
                    'errors' => array_column(array_filter($results, fn($r) => $r['status'] === 'error'), 'message'),
                ]);
                
                return response()->json([
                    'error' => 'No PDFs to download',
                    'message' => 'Failed to generate any PDFs from the provided session IDs',
                    'code' => 'NO_PDFS_GENERATED',
                    'details' => [
                        'total' => count($sessionIds),
                        'successful' => 0,
                        'failed' => count($results),
                        'errors' => array_map(function($result) {
                            return [
                                'session_id' => $result['session_id'],
                                'message' => $result['message']
                            ];
                        }, array_filter($results, fn($r) => $r['status'] === 'error'))
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Create zip file if multiple PDFs or metadata requested
            if (count($successfulResults) > 1 || $includeMetadata) {
                // Generate a clean filename for the zip
                $sanitizedBatchName = preg_replace('/[^a-z0-9_-]/i', '_', $batchName);
                $zipFileName = $sanitizedBatchName . '_' . date('Ymd_His') . '.zip';
                $zipPath = 'temp/' . $zipFileName;
                
                // Ensure temp directory exists
                if (!Storage::disk('public')->exists('temp')) {
                    Storage::disk('public')->makeDirectory('temp');
                }
                
                // Create zip file
                $fullZipPath = Storage::disk('public')->path($zipPath);
                $zip = new \ZipArchive();
                
                if ($zip->open($fullZipPath, \ZipArchive::CREATE) === TRUE) {
                    // Add PDFs to zip
                    foreach ($successfulResults as $result) {
                        if (Storage::disk('public')->exists($result['path'])) {
                            // Get a clean filename for the PDF
                            $pdfFilename = $this->getCleanFilename($result);
                            
                            $zip->addFile(
                                Storage::disk('public')->path($result['path']),
                                $pdfFilename
                            );
                        }
                    }
                    
                    // Add metadata file if requested
                    if ($includeMetadata) {
                        $metadata = [
                            'batch_id' => $batchId,
                            'generated_at' => now()->toISOString(),
                            'total_pdfs' => count($successfulResults),
                            'generated_by' => $request->user() ? $request->user()->name : 'System',
                            'pdfs' => array_map(function($result) {
                                return [
                                    'session_id' => $result['session_id'],
                                    'filename' => $this->getCleanFilename($result),
                                    'application_number' => $result['application_number'] ?? null,
                                    'applicant_name' => $result['applicant_name'] ?? null,
                                    'application_type' => $result['application_type'] ?? null,
                                    'generated_at' => $result['generated_at'] ?? now()->toISOString(),
                                ];
                            }, $successfulResults)
                        ];
                        
                        // Create metadata JSON file
                        $metadataJson = json_encode($metadata, JSON_PRETTY_PRINT);
                        $metadataPath = sys_get_temp_dir() . '/batch_metadata_' . $batchId . '.json';
                        file_put_contents($metadataPath, $metadataJson);
                        
                        // Add to zip
                        $zip->addFile($metadataPath, 'metadata.json');
                    }
                    
                    $zip->close();
                    
                    // Clean up temp metadata file if created
                    if ($includeMetadata && file_exists($metadataPath)) {
                        unlink($metadataPath);
                    }
                    
                    // Log successful batch download
                    Log::info('Batch PDF download completed', [
                        'batch_id' => $batchId,
                        'total' => count($sessionIds),
                        'successful' => count($successfulResults),
                        'failed' => count($results) - count($successfulResults),
                        'zip_path' => $zipPath,
                        'zip_size' => Storage::disk('public')->size($zipPath),
                    ]);
                    
                    // Set headers for download
                    $headers = [
                        'Content-Type' => 'application/zip',
                        'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                        'Cache-Control' => 'no-cache, no-store, must-revalidate',
                        'Pragma' => 'no-cache',
                        'Expires' => '0',
                    ];
                    
                    // Schedule cleanup of the zip file after 1 hour
                    $this->scheduleFileCleanup($zipPath, 3600);
                    
                    return Storage::disk('public')->download($zipPath, $zipFileName, $headers);
                } else {
                    // Failed to create zip
                    Log::error('Batch PDF download failed: Unable to create zip file', [
                        'batch_id' => $batchId,
                        'zip_path' => $zipPath,
                    ]);
                    
                    return response()->json([
                        'error' => 'Failed to create zip file',
                        'message' => 'An error occurred while creating the zip archive',
                        'code' => 'ZIP_CREATION_FAILED'
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
            
            // Single PDF download (no zip needed)
            if (count($successfulResults) === 1) {
                $result = $successfulResults[0];
                
                if (Storage::disk('public')->exists($result['path'])) {
                    // Log successful single PDF download
                    Log::info('Single PDF download from batch', [
                        'batch_id' => $batchId,
                        'session_id' => $result['session_id'],
                        'pdf_path' => $result['path'],
                    ]);
                    
                    // Get filename
                    $filename = $this->getCleanFilename($result);
                    
                    // Set headers for download
                    $headers = [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        'Cache-Control' => 'private, max-age=600',
                        'Pragma' => 'private',
                        'Expires' => gmdate('D, d M Y H:i:s', time() + 600) . ' GMT',
                    ];
                    
                    return Storage::disk('public')->download($result['path'], $filename, $headers);
                }
            }
            
            // Should not reach here if we have successful results
            return response()->json([
                'error' => 'No PDFs to download',
                'message' => 'Failed to generate any PDFs from the provided session IDs',
                'code' => 'NO_PDFS_GENERATED'
            ], Response::HTTP_NOT_FOUND);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Batch PDF download failed: Validation error', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'The provided data is invalid',
                'code' => 'VALIDATION_FAILED',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Batch PDF download failed: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Failed to process batch download',
                'message' => 'An unexpected error occurred while processing your request',
                'code' => 'BATCH_PROCESSING_FAILED'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Process a chunk of session IDs for batch PDF generation
     * 
     * @param array $sessionIds Array of session IDs to process
     * @param string $batchId Batch identifier for tracking
     * @param int $chunkIndex Index of the current chunk
     * @return array Results of PDF generation
     */
    private function processSessionIdChunk(array $sessionIds, string $batchId, int $chunkIndex): array
    {
        $results = [];
        
        foreach ($sessionIds as $index => $sessionId) {
            try {
                // Find the application state
                $state = ApplicationState::where('session_id', $sessionId)
                    ->where('current_step', 'completed')
                    ->first();
                
                if (!$state) {
                    $results[] = [
                        'session_id' => $sessionId,
                        'status' => 'error',
                        'message' => 'Application not found or not completed',
                        'code' => 'APPLICATION_NOT_FOUND'
                    ];
                    continue;
                }
                
                $formData = $state->form_data ?? [];
                
                // Check if PDF already generated
                if (isset($formData['pdfPath']) && Storage::disk('public')->exists($formData['pdfPath'])) {
                    // Use existing PDF
                    $pdfPath = $formData['pdfPath'];
                    
                    // Extract metadata for the result
                    $applicantName = $this->extractApplicantName($formData);
                    $applicationType = $this->extractApplicationType($formData);
                    $applicationNumber = $formData['applicationNumber'] ?? null;
                    
                    $results[] = [
                        'session_id' => $sessionId,
                        'status' => 'success',
                        'path' => $pdfPath,
                        'generated_at' => $formData['pdfGeneratedAt'] ?? now()->toISOString(),
                        'applicant_name' => $applicantName,
                        'application_type' => $applicationType,
                        'application_number' => $applicationNumber,
                        'from_cache' => true
                    ];
                } else {
                    // Generate new PDF
                    $pdfPath = $this->pdfGenerator->generateApplicationPDF($state);
                    
                    // Save PDF path
                    $formData['pdfPath'] = $pdfPath;
                    $formData['pdfGeneratedAt'] = now()->toISOString();
                    $formData['pdfBatchId'] = $batchId;
                    $state->update(['form_data' => $formData]);
                    
                    // Extract metadata for the result
                    $applicantName = $this->extractApplicantName($formData);
                    $applicationType = $this->extractApplicationType($formData);
                    $applicationNumber = $formData['applicationNumber'] ?? null;
                    
                    $results[] = [
                        'session_id' => $sessionId,
                        'status' => 'success',
                        'path' => $pdfPath,
                        'generated_at' => $formData['pdfGeneratedAt'],
                        'applicant_name' => $applicantName,
                        'application_type' => $applicationType,
                        'application_number' => $applicationNumber,
                        'from_cache' => false
                    ];
                }
                
                // Log progress for individual PDF
                Log::info('Batch PDF progress', [
                    'batch_id' => $batchId,
                    'chunk' => $chunkIndex,
                    'index' => $index,
                    'session_id' => $sessionId,
                    'status' => 'success',
                ]);
                
            } catch (\Exception $e) {
                // Log error
                Log::error('Error generating PDF in batch', [
                    'batch_id' => $batchId,
                    'chunk' => $chunkIndex,
                    'index' => $index,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $results[] = [
                    'session_id' => $sessionId,
                    'status' => 'error',
                    'message' => 'Failed to generate PDF: ' . $e->getMessage(),
                    'code' => 'PDF_GENERATION_FAILED'
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Extract applicant name from form data
     * 
     * @param array $formData The form data
     * @return string The applicant name
     */
    private function extractApplicantName(array $formData): string
    {
        $responses = $formData['formResponses'] ?? [];
        
        $firstName = $responses['firstName'] ?? $responses['first_name'] ?? '';
        $lastName = $responses['lastName'] ?? $responses['surname'] ?? $responses['last_name'] ?? '';
        
        $name = trim($firstName . ' ' . $lastName);
        return $name ?: 'Unknown Applicant';
    }
    
    /**
     * Extract application type from form data
     * 
     * @param array $formData The form data
     * @return string The application type
     */
    private function extractApplicationType(array $formData): string
    {
        $formId = $formData['formId'] ?? '';
        $employer = $formData['employer'] ?? '';
        $hasAccount = $formData['hasAccount'] ?? false;
        
        $types = [
            'account_holder_loan_application.json' => 'Account Holder Loan',
            'ssb_account_opening_form.json' => 'SSB Loan',
            'individual_account_opening.json' => 'ZB Account Opening',
            'smes_business_account_opening.json' => 'SME Business Account',
        ];
        
        if (isset($types[$formId])) {
            return $types[$formId];
        }
        
        // Fallback based on employer and account status
        if ($employer === 'goz-ssb') {
            return 'SSB Loan';
        }
        
        if ($employer === 'entrepreneur') {
            return 'SME Business Account';
        }
        
        if (!$hasAccount) {
            return 'ZB Account Opening';
        }
        
        return 'Account Holder Loan';
    }
    
    /**
     * Get a clean filename for a PDF result
     * 
     * @param array $result The PDF result
     * @return string A clean filename
     */
    private function getCleanFilename(array $result): string
    {
        $applicantName = $result['applicant_name'] ?? 'Unknown';
        $applicationType = $result['application_type'] ?? 'Application';
        $applicationNumber = $result['application_number'] ?? '';
        
        // Clean up applicant name for filename
        $cleanName = preg_replace('/[^a-z0-9]/i', '_', $applicantName);
        $cleanName = preg_replace('/_+/', '_', $cleanName); // Replace multiple underscores with one
        $cleanName = trim($cleanName, '_');
        
        // Clean up application type
        $cleanType = preg_replace('/[^a-z0-9]/i', '_', $applicationType);
        $cleanType = preg_replace('/_+/', '_', $cleanType);
        $cleanType = trim($cleanType, '_');
        
        // Build filename
        $filename = $cleanName . '_' . $cleanType;
        
        if ($applicationNumber) {
            $filename .= '_' . $applicationNumber;
        }
        
        $filename .= '_' . date('Ymd') . '.pdf';
        
        return $filename;
    }
    
    /**
     * Schedule a file for cleanup after a specified time
     * 
     * @param string $path The file path to clean up
     * @param int $delay The delay in seconds before cleanup
     * @return void
     */
    private function scheduleFileCleanup(string $path, int $delay): void
    {
        // In a real application, this would use a job queue
        // For now, we'll just log the intention
        Log::info('File scheduled for cleanup', [
            'path' => $path,
            'cleanup_at' => now()->addSeconds($delay)->toISOString(),
        ]);
        
        // In a real application with Laravel's job queue:
        // CleanupFileJob::dispatch($path)->delay(now()->addSeconds($delay));
    }
    
    /**
     * Get batch processing status
     * 
     * @param Request $request The HTTP request
     * @param string $batchId The batch ID
     * @return Response The HTTP response
     */
    public function batchStatus(Request $request, string $batchId)
    {
        $progressKey = "pdf_batch_{$batchId}_progress";
        $progress = Cache::get($progressKey);
        
        if (!$progress) {
            return response()->json([
                'error' => 'Batch not found',
                'message' => 'The specified batch ID was not found or has expired',
                'code' => 'BATCH_NOT_FOUND'
            ], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json([
            'batch_id' => $batchId,
            'status' => $progress['status'],
            'progress' => [
                'total' => $progress['total'],
                'processed' => $progress['processed'],
                'successful' => $progress['successful'],
                'failed' => $progress['failed'],
                'percent_complete' => $progress['total'] > 0 
                    ? round(($progress['processed'] / $progress['total']) * 100, 1) 
                    : 0,
            ],
            'started_at' => $progress['started_at'],
            'completed_at' => $progress['completed_at'] ?? null,
        ]);
    }
}
