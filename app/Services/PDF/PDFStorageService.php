<?php

namespace App\Services\PDF;

use App\Models\ApplicationState;
use App\Services\PDFLoggingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PDFStorageService
{
    private PDFLoggingService $logger;

    public function __construct(PDFLoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Store PDF file and return file information
     */
    public function storePDF(string $pdfContent, ApplicationState $applicationState, array $metadata = []): array
    {
        try {
            $filename = $this->generateFilename($applicationState);
            $path = $this->getStoragePath($applicationState);
            $fullPath = $path . '/' . $filename;

            // Ensure directory exists
            $this->ensureDirectoryExists($path);

            // Store the PDF
            $stored = Storage::disk('local')->put($fullPath, $pdfContent);

            if (!$stored) {
                throw new \Exception('Failed to store PDF file');
            }

            // Get file information
            $fileInfo = $this->getFileInfo($fullPath, $applicationState, $metadata);

            // Log successful storage
            $this->logger->logInfo('PDF stored successfully', [
                'session_id' => $applicationState->session_id,
                'filename' => $filename,
                'path' => $fullPath,
                'size' => $fileInfo['size'],
            ]);

            return $fileInfo;

        } catch (\Exception $e) {
            $this->logger->logError('Failed to store PDF', [
                'session_id' => $applicationState->session_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a unique filename for the PDF
     */
    private function generateFilename(ApplicationState $applicationState): string
    {
        $referenceCode = $applicationState->reference_code ?? 'DRAFT';
        $timestamp = now()->format('Y-m-d_H-i-s');
        $sessionHash = substr(md5($applicationState->session_id), 0, 8);
        
        return "application_{$referenceCode}_{$timestamp}_{$sessionHash}.pdf";
    }

    /**
     * Get storage path for the PDF
     */
    private function getStoragePath(ApplicationState $applicationState): string
    {
        $year = $applicationState->created_at->format('Y');
        $month = $applicationState->created_at->format('m');
        $channel = $applicationState->channel;
        
        return "pdfs/{$year}/{$month}/{$channel}";
    }

    /**
     * Ensure storage directory exists
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!Storage::disk('local')->exists($path)) {
            Storage::disk('local')->makeDirectory($path, 0755, true);
        }
    }

    /**
     * Get file information
     */
    private function getFileInfo(string $path, ApplicationState $applicationState, array $metadata): array
    {
        $size = Storage::disk('local')->size($path);
        $url = $this->generateSecureUrl($path, $applicationState);

        return [
            'filename' => basename($path),
            'path' => $path,
            'size' => $size,
            'size_human' => $this->formatBytes($size),
            'url' => $url,
            'created_at' => now()->toISOString(),
            'session_id' => $applicationState->session_id,
            'reference_code' => $applicationState->reference_code,
            'metadata' => $metadata,
        ];
    }

    /**
     * Generate a secure URL for PDF access
     */
    private function generateSecureUrl(string $path, ApplicationState $applicationState): string
    {
        // Generate a secure token for PDF access
        $token = $this->generateAccessToken($path, $applicationState);
        
        // Use admin.pdf.download route if in admin context, otherwise use application.pdf.download
        return route('application.pdf.download', [
            'sessionId' => $applicationState->session_id,
        ]);
    }

    /**
     * Generate access token for PDF download
     */
    private function generateAccessToken(string $path, ApplicationState $applicationState): string
    {
        $data = [
            'path' => $path,
            'session_id' => $applicationState->session_id,
            'expires_at' => now()->addHours(24)->timestamp,
        ];

        $payload = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return $payload . '.' . $signature;
    }

    /**
     * Validate and decode access token
     */
    public function validateAccessToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                return null;
            }

            [$payload, $signature] = $parts;
            
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $payload, config('app.key'));
            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            // Decode payload
            $data = json_decode(base64_decode($payload), true);
            if (!$data) {
                return null;
            }

            // Check expiration
            if (isset($data['expires_at']) && $data['expires_at'] < time()) {
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            Log::warning('Invalid PDF access token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Retrieve PDF content
     */
    public function retrievePDF(string $path): ?string
    {
        try {
            if (!Storage::disk('local')->exists($path)) {
                return null;
            }

            return Storage::disk('local')->get($path);

        } catch (\Exception $e) {
            $this->logger->logError('Failed to retrieve PDF', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete PDF file
     */
    public function deletePDF(string $path): bool
    {
        try {
            if (Storage::disk('local')->exists($path)) {
                $deleted = Storage::disk('local')->delete($path);
                
                if ($deleted) {
                    $this->logger->logInfo('PDF deleted', ['path' => $path]);
                }
                
                return $deleted;
            }

            return true; // File doesn't exist, consider it deleted

        } catch (\Exception $e) {
            $this->logger->logError('Failed to delete PDF', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up old PDF files
     */
    public function cleanupOldFiles(int $daysOld = 30): int
    {
        $deletedCount = 0;
        $cutoffDate = now()->subDays($daysOld);

        try {
            $files = Storage::disk('local')->allFiles('pdfs');
            
            foreach ($files as $file) {
                $lastModified = Storage::disk('local')->lastModified($file);
                
                if ($lastModified < $cutoffDate->timestamp) {
                    if (Storage::disk('local')->delete($file)) {
                        $deletedCount++;
                    }
                }
            }

            $this->logger->logInfo('PDF cleanup completed', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toISOString(),
            ]);

        } catch (\Exception $e) {
            $this->logger->logError('PDF cleanup failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $deletedCount;
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        try {
            $files = Storage::disk('local')->allFiles('pdfs');
            $totalSize = 0;
            $fileCount = count($files);

            foreach ($files as $file) {
                $totalSize += Storage::disk('local')->size($file);
            }

            return [
                'file_count' => $fileCount,
                'total_size' => $totalSize,
                'total_size_human' => $this->formatBytes($totalSize),
                'average_size' => $fileCount > 0 ? round($totalSize / $fileCount) : 0,
                'average_size_human' => $fileCount > 0 ? $this->formatBytes(round($totalSize / $fileCount)) : '0 B',
            ];

        } catch (\Exception $e) {
            $this->logger->logError('Failed to get storage stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'file_count' => 0,
                'total_size' => 0,
                'total_size_human' => '0 B',
                'average_size' => 0,
                'average_size_human' => '0 B',
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
